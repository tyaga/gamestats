<?
/*
db.games.insert({name:"test1", "game_id":1, "secret":"fd4efrbtes"})
db.games.insert({name:"test2", "game_id":2, "secret":"ioyukj78j"})
db.games.insert({name:"test3", "game_id":3, "secret":"ioyukj78j"})
 *
 * */
define('STAT_URL', 'http://localhost:9090/stat');
define('STAT_COUNT', 4000);

$games = array(
	1 => array(
		"id" => 1,
		"secret" => "fd4efrbtes",
		"users" => array( 431, 432, 433, 434 )
	),
	2 => array(
		"id" => 2,
		"secret" => "ioyukj78j",
		"users" => array( 631, 632, 633, 634 )
	),
	3 => array(
		"id" => 3,
		"secret" => "ioyukj78j",
		"users" => array( 931, 932, 933, 934 )
	),
);

for ($i = 0; $i < STAT_COUNT; $i++) {
	//$game = $games[array_rand($games)];
	$game = $games[2];
	$user = $game['users'][array_rand($game['users'])];

	post_request(STAT_URL, array(
		'game_id' => $game['id'],
		'user_id' => $user,
		'wins' => rand(2,45),
		'level' => rand(1,5)
	), $game['secret']);
	sleep(1);
}

function post_request($url, $params, $secret) {
	$post_params = array( 'http' => array(
		'method' => 'POST', 'content' => sign_request($params, $secret)
	));
	$ctx = stream_context_create($post_params);
	$fp = @fopen($url, 'rb', false, $ctx);
	return @stream_get_contents($fp);
}

function post_async_request($url, $params, $secret) {
	$post_string = sign_request($params, $secret);
	$parts=parse_url($url);
	$fp = fsockopen($parts['host'], isset($parts['port'])?$parts['port']:80, $errno, $errstr, 30);
	$out = "POST ".$parts['path']." HTTP/1.1\r\n";
	$out.= "Host: ".$parts['host']."\r\n";
	$out.= "Content-Type: application/x-www-form-urlencoded\r\n";
	$out.= "Content-Length: ".strlen($post_string)."\r\n";
	$out.= "Connection: Close\r\n\r\n";
	if ($post_string) $out.= $post_string;
	fwrite($fp, $out);
	fclose($fp);
}

function sign_request($params, $secret) {
	$post_params = array();
	ksort($params);
	foreach ($params as $key => $val) {
		if (is_array($val)) $val = implode(',', $val);
		$post_params[] = $key.'='.urlencode($val);
	}
	$post_params[] = "sig=" . md5(implode('', $post_params) . $secret);
	return implode('&', $post_params);
}
