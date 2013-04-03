<?php

// pipe a connection into itself

require __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$socket = new React\Socket\Server($loop);

$socket->on('connection', function ($conn) {
    $conn->pipe($conn);
});

echo "Socket server listening on port 4000.\n";
echo "You can connect to it by running: telnet localhost 4000\n";

$request->on('data', function ($chunk) use (&$data) {
    $data .= $chunk;
var_dump('chunk : '.$chunk);
});
$request->on('end', function () use (&$data) {
    var_dump($data);
});

$socket->listen(4000);
$loop->run();
