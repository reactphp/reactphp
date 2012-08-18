<?php

// script to spawn multiple child processes

require __DIR__.'/../vendor/autoload.php';

$loop = new React\EventLoop\StreamSelectLoop();
$factory = new React\ChildProcess\factory($loop);

$processA = $factory->spawn('php', array('-r', 'foreach (range(1, 3) as $i) { echo $i, PHP_EOL; sleep(1); } '));

$processA->stdout->on('data', function ($data) {
    echo "[A] [STDOUT]: ";
    var_dump($data);
});

$processA->stderr->on('data', function ($data) {
    echo "[A] [STDERR]: ";
    var_dump($data);
});

$processA->on('exit', function ($status) {
    echo "[A] [EXIT]: ";
    var_dump($status);
});

$processB = $factory->spawn('php', array('-r', 'foreach (range(1, 6) as $i) { echo $i, PHP_EOL; sleep(1); } '));

$processB->stdout->on('data', function ($data) {
    echo "[B] [STDOUT]: ";
    var_dump($data);
});

$processB->stderr->on('data', function ($data) {
    echo "[B] [STDERR]: ";
    var_dump($data);
});

$processB->on('exit', function ($status) {
    echo "[B] [EXIT]:";
    var_dump($status);
});

$loop->run();
