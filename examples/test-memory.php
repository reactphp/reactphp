<?php

// script to test for mem leaks
// You can test it using:
// siege localhost:8080

require __DIR__.'/../vendor/autoload.php';

$loop = new React\EventLoop\StreamSelectLoop();
$socket = new React\Socket\Server($loop);
$http = new React\Http\Server($socket, $loop);

$i = 0;

$http->on('request', function ($request, $response) use (&$i) {
    $i++;
    $response->writeHead();
    $response->end("Hello World!\n");
});

$loop->addPeriodicTimer(2, function () use (&$i) {
    $kmem = memory_get_usage(true) / 1024;
    echo "Request: $i\n";
    echo "Memory: $kmem KiB\n";
});

$socket->listen(8080);
$loop->run();
