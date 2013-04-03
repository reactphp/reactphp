#!/bin/bash
#
# Using git-subsplit
# https://github.com/dflydev/git-subsplit

GIT_SUBSPLIT=$(pwd)/$(dirname $0)/git-subsplit.sh

$GIT_SUBSPLIT init https://github.com/reactphp/react

$GIT_SUBSPLIT update

$GIT_SUBSPLIT publish "
    src/React/EventLoop:git@github.com:reactphp/event-loop.git
    src/React/Stream:git@github.com:reactphp/stream.git
    src/React/Cache:git@github.com:reactphp/cache.git
    src/React/Socket:git@github.com:reactphp/socket.git
    src/React/SocketClient:git@github.com:reactphp/socket-client.git
    src/React/Http:git@github.com:reactphp/http.git
    src/React/HttpClient:git@github.com:reactphp/http-client.git
    src/React/Dns:git@github.com:reactphp/dns.git
" --heads=master
