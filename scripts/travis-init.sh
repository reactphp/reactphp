#!/bin/bash

sudo apt-get install -y libevent-dev
if [ "$TRAVIS_PHP_VERSION" != "hhvm" ] && [ "\$(php --re libevent | grep 'does not exist')" != "" ]; then
     wget http://pecl.php.net/get/libevent-0.0.5.tgz;
    tar -xzf libevent-0.0.5.tgz;
    cd libevent-0.0.5 && phpize && ./configure && make && sudo make install && cd ../;
    echo "extension=libevent.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`;
fi

echo "yes" | pecl install event

(git clone --recursive https://github.com/m4rw3r/php-libev && cd php-libev && phpize && ./configure --with-libev && make && make install)
echo "extension=libev.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

composer self-update
composer install --dev --prefer-source
