<?php

require __DIR__.'/../vendor/autoload.php';

$loop   = new React\EventLoop\LibEvLoop();
$socket = new React\Socket\Server($loop);
$i      = 0;

$socket->on('connection', function ($conn) use (&$i, $loop) {
    $i++;

    $conn->on('end', function () use (&$i) {
        $i--;
    });
});

$loop->addPeriodicTimer(2, function () use (&$i) {
    echo "$i open connections?\n";
});

$socket->listen('tcp://127.0.0.1:8080');
$loop->run();
