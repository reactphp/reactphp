#!/bin/bash
set -e
set -o pipefail

if [ "$TRAVIS_PHP_VERSION" != "hhvm" ]; then

    # install "libevent" (used by 'event' and 'libevent' PHP extensions)
    sudo apt-get install -y libevent-dev

    # install 'event' PHP extension
    echo "yes" | pecl install event

    # install 'libevent' PHP extension
    curl http://pecl.php.net/get/libevent-0.0.5.tgz | tar -xz
    pushd libevent-0.0.5
    phpize
    ./configure
    make
    make install
    popd
    echo "extension=libevent.so" >> "$(php -r 'echo php_ini_loaded_file();')"

    # install 'libev' PHP extension
    git clone --recursive https://github.com/m4rw3r/php-libev
    pushd php-libev
    phpize
    ./configure --with-libev
    make
    make install
    popd
    echo "extension=libev.so" >> "$(php -r 'echo php_ini_loaded_file();')"

fi

composer self-update
composer install --dev --prefer-source
