<?php

// script to spawn multiple child processes

require __DIR__.'/../vendor/autoload.php';

$loop = new React\EventLoop\StreamSelectLoop();
$factory = new React\ChildProcess\factory($loop);

$processA = $factory->spawn('php', array('-r', 'foreach (range(1, 3) as $i) { echo $i, PHP_EOL; sleep(1); } fputs(STDERR, "Bye.");'));

$processA->stdout->on('data', function ($data) {
    echo '[A] ' . green('[STDOUT]') . ' ';
    var_dump($data);
});

$processA->stderr->on('data', function ($data) {
    echo '[A] ' . red('[STDERR]') . ' ';
    var_dump($data);
});

$processA->on('exit', function ($status) {
    echo '[A] ' . yellow('[EXIT]') . '   exited with status code ', (string) $status, PHP_EOL;
});

$processB = $factory->spawn('php', array('-r', 'foreach (range(1, 6) as $i) { echo $i, PHP_EOL; sleep(1); } fputs(STDERR, "Bye.");'));

$processB->stdout->on('data', function ($data) {
    echo '[B] ' . green('[STDOUT]') . ' ';
    var_dump($data);
});

$processB->stderr->on('data', function ($data) {
    echo '[B] ' . red('[STDERR]') . ' ';
    var_dump($data);
});

$processB->on('exit', function ($status) {
    echo '[B] ' . yellow('[EXIT]') . '   exited with status code ', (string) $status, PHP_EOL;
});

$loop->run();

function red($str) {
    return "\033[31m{$str}\033[0m";
}

function green($str) {
    return "\033[32m{$str}\033[0m";
}

function yellow($str) {
    return "\033[33m{$str}\033[0m";
}
