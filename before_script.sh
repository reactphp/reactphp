sudo apt-get install -y libevent-dev
sh -c " if [ [ \"$TRAVIS_PHP_VERSION\" != \"hhvm\" ] && [ \"\$(php --re libevent | grep 'does not exist')\" != '' ] ]; then
          wget http://pecl.php.net/get/libevent-0.0.5.tgz;
          tar -xzf libevent-0.0.5.tgz;
          cd libevent-0.0.5 && phpize && ./configure && make && sudo make install;
          echo "extension=libevent.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`;
        fi"
composer self-update
composer install --dev --prefer-source
