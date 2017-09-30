FROM php:7.0-alpine

MAINTAINER evalsocket<evalsocket@gmail.com>

RUN apk update && apk upgrade && \
    apk add --no-cache bash git openssh
RUN apk add --update zlib-dev

WORKDIR "/tmp"

RUN git clone https://github.com/evalsocket/react.git

WORKDIR "./react"

RUN docker-php-ext-install -j$(getconf _NPROCESSORS_ONLN) iconv

RUN rm -rf /tmp/*

