#!/bin/bash
#
# Using git-subsplit
# https://github.com/dflydev/git-subsplit

GIT_SUBSPLIT=$(pwd)/$(dirname $0)/git-subsplit.sh

$GIT_SUBSPLIT init https://github.com/reactphp/react

$GIT_SUBSPLIT update

$GIT_SUBSPLIT publish "
    src/EventLoop:git@github.com:reactphp/event-loop.git
    src/Stream:git@github.com:reactphp/stream.git
    src/Cache:git@github.com:reactphp/cache.git
    src/Socket:git@github.com:reactphp/socket.git
    src/SocketClient:git@github.com:reactphp/socket-client.git
    src/Http:git@github.com:reactphp/http.git
    src/HttpClient:git@github.com:reactphp/http-client.git
    src/Dns:git@github.com:reactphp/dns.git
" --heads=master
