#!/bin/bash
#
# Using git-subsplit
# https://github.com/dflydev/git-subsplit

GIT_SUBSPLIT=$(pwd)/$(dirname $0)/git-subsplit.sh

$GIT_SUBSPLIT init https://github.com/react-php/react

$GIT_SUBSPLIT update

$GIT_SUBSPLIT publish "
    src/React/EventLoop:git@github.com:react-php/event-loop.git
    src/React/Stream/:git@github.com:react-php/stream.git
    src/React/Socket/:git@github.com:react-php/socket.git
    src/React/Http/:git@github.com:react-php/http.git
    src/React/Espresso/:git@github.com:react-php/espresso.git
    src/React/Dns/:git@github.com:react-php/dns.git
" --heads=master
