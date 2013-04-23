package main

import (
	"crypto/md5"
	"flag"
	"fmt"
	"io"
	"io/ioutil"
	"labix.org/v2/mgo"
	"labix.org/v2/mgo/bson"
	"log"
	"net/http"
	"net/url"
	"os"
	"os/signal"
	"runtime"
	"sort"
	"strconv"
	"sync"
	"syscall"
	"time"
)

var (
	verbose *bool
	db      *mgo.Database
)

func main() {
	// set threads count
	runtime.GOMAXPROCS(runtime.NumCPU() - 1)

	// parse flags
	verbose = flag.Bool("verbose", true, "Log all what's happening")
	listen_port := flag.String("port", ":9090", "Port for listen to")
	mongo_dsn := flag.String("db", "localhost", "Mongo host and port")
	flag.Parse()

	// init database
	session, err := mgo.Dial(*mongo_dsn)
	if err != nil {
		panic(err)
	}
	defer session.Close()
	db = session.DB("gamestat")

	// load games from database
	games = *LoadGames()

	// signal and channel 
	// for reload games from database
	chanSignal := make(chan os.Signal, 1)
	signal.Notify(chanSignal, syscall.SIGHUP)
	go sighandle(chanSignal)

	// channels for prepare and write down stats
	chanPrepare := make(chan *Hash)
	chanWrite := make(chan *Hash)

	// preparator!
	go prepare(chanPrepare, chanWrite)

	// writestignator!
	go write(chanWrite)

	// listen and serve and handle
	http.HandleFunc("/stat", func(w http.ResponseWriter, r *http.Request) {
		var body string
		if r.Method == "POST" {
			body_r, _ := ioutil.ReadAll(r.Body)
			body = string(body_r)
		} else {
			body = r.URL.RawQuery
		}

		stat := NewHashFromRawQuery(&body)
		LOG("Got stat: ", *stat)

		chanPrepare <- stat

		// 	immediately response
		w.Header().Set("Content-type", "application/json")
		fmt.Fprint(w, `{"res": true}`)
	})

	http.ListenAndServe(*listen_port, nil)
}

func prepare(chanPrepare chan *Hash, chanWrite chan *Hash) {
	for {
		stat := <-chanPrepare

		// check if game_id passed
		game_idx, ok := (*stat)["game_id"].(string)
		if !ok {
			LOG("No game_id passed", *stat)
			continue
		}
		// check if game_id is int
		game_id, err := strconv.Atoi(game_idx)
		if err != nil {
			LOG("Wrong game_id", *stat)
			continue
		}
		// check if game_id exists in games
		// lock for read, handle reloading on HUP
		gamesLock.RLock()
		game, ok := games[game_id]
		gamesLock.RUnlock()

		if !ok {
			LOG("Wrong game", *stat)
			continue
		}

		// check if signature passed
		sig, ok := (*stat)["sig"]
		if !ok {
			LOG("No sig passed", *stat)
			continue
		}
		delete(*stat, "sig")

		// check signature 
		arr := (*stat).sortedKeys()
		str := ""
		for _, v := range arr {
			str = str + v + "=" + (*stat)[v].(string)
		}
		str = str + game.Secret
		if signMd5(str) != sig {
			LOG("Wrong sig, expected: ", signMd5(str), *stat)
			continue
		}

		// add Timestamp to the stat
		(*stat)["Timestamp"] = time.Now()

		// send to write channel
		chanWrite <- stat
	}
}

func write(chanWrite chan *Hash) {
	for {
		stat := <-chanWrite

		err := db.C("stats_log").Insert(stat)
		if err != nil {
			LOG("Error writing stat: ", *stat)
			continue
		}

		LOG("Wrote down: ", *stat)
	}
}

func sighandle(chanSignal chan os.Signal) {
	for _ = range chanSignal {
		LOG("SIGHUP received, reloading games from database")
		games = *LoadGames()
	}
}

func LOG(str ...interface{}) {
	if !*verbose {
		return
	}
	log.Printf("[statserve] %s", str)
}

func signMd5(str string) string {
	h := md5.New()
	io.WriteString(h, str)
	return fmt.Sprintf("%x", h.Sum(nil))
}

/*
TYPE Hash
NewHashFromRawQuery
Hash.sortedKeys
*/
type Hash map[string]interface{}

func NewHashFromRawQuery(rawQuery *string) *Hash {
	queryMap, _ := url.ParseQuery(*rawQuery)
	vals := make(Hash)
	for k, v := range queryMap {
		vals[k] = v[0] // never ever assume having multiple stats
	}
	return &vals
}

func (self *Hash) sortedKeys() []string {
	arr := []string{}
	for k, _ := range *self {
		arr = append(arr, k)
	}
	sort.Strings(arr)
	return arr
}

/*
TYPE Game
TYPE Games
LoadGames
*/
type Game struct {
	ID      bson.ObjectId `bson:"_id,omitempty"`
	Name    string
	Game_id int
	Secret  string
}

var (
	games     Games
	gamesLock sync.RWMutex
)

type Games map[int]Game

func LoadGames() *Games {
	gamesLock.Lock()
	defer gamesLock.Unlock()

	var g []Game
	err := db.C("games").Find(bson.M{}).All(&g)
	if err != nil {
		panic(err)
	}
	games_map := make(Games)
	for i := range g {
		games_map[g[i].Game_id] = g[i]
	}
	return &games_map
}
