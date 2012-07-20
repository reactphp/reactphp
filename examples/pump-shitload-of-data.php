<?php

// serve a lot of data over TCP
// useful for profiling
// you can run this command to profile with xdebug:
// php -d xdebug.profiler_enable=on examples/pump-shitload-of-data.php

require __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);

$socket->on('connection', function ($conn) {
    $shitload = str_repeat('a', 1024*1024*32);
    $conn->write($shitload);
    $conn->end();
});

echo "Socket server listening on port 4000.\n";
echo "You can connect to it by running: telnet localhost 4000\n";

$socket->listen(4000);
$loop->run();
