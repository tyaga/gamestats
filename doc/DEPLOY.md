# create cache dir
mkdir cache
chmod 777 cache

# update deps
composer update

# update deps for serve
go get labix.org/v2/mgo

# build serve
pushd statserve
go build statserve.go
popd

