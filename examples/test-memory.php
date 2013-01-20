<?php

require __DIR__.'/../vendor/autoload.php';

$loop = new React\EventLoop\StreamSelectLoop();

$i = 0;

$loop->addPeriodicTimer(0.001, function () use (&$i, $loop) {
    $i++;

    // $loop->addTimer(1, function ($timer) {
    // });

    $loop->addPeriodicTimer(1, function ($timer) {
        $timer->cancel();
    });
});

$loop->addPeriodicTimer(2, function () use (&$i) {
    $kmem = memory_get_usage(true) / 1024;
    echo "Run: $i\n";
    echo "Memory: $kmem KiB\n";
});

$loop->run();
