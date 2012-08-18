<?php

// script to spawn multiple child processes

require __DIR__.'/../vendor/autoload.php';

$loop = new React\EventLoop\StreamSelectLoop();
$factory = new React\ChildProcess\Factory($loop);

$commands = array(
    'A' => array('cmd' => 'php', 'args' => array('-r', 'foreach (range(1, 3) as $i) { echo $i, PHP_EOL; sleep(1); } fputs(STDERR, "Bye.");')),
    'B' => array('cmd' => 'php', 'args' => array('-r', 'foreach (range(1, 6) as $i) { echo $i, PHP_EOL; sleep(1); } fputs(STDERR, "Bye.");')),
    'C' => array('cmd' => 'php', 'args' => array('-r', 'foreach (range(1, 9) as $i) { echo $i, PHP_EOL; sleep(1); } fputs(STDERR, "Bye.");')),
);

foreach ($commands as $id => $command) {
    $idLabel = "[{$id}] ";
    $process = $factory->spawn($command['cmd'], $command['args']);
    echo $idLabel, blue('[PID]') . '    pid is ', (string) $process->getPid(), PHP_EOL;
    echo $idLabel, blue('[CMD]') . '    ', $process->getCommand(), PHP_EOL;

    $process->stdout->on('data', function ($data) use ($idLabel) {
        echo $idLabel, green('[STDOUT]') . ' ';
        var_dump($data);
    });

    $process->stderr->on('data', function ($data) use ($idLabel) {
        echo $idLabel, red('[STDERR]') . ' ';
        var_dump($data);
    });

    $process->on('exit', function ($status) use ($idLabel) {
        echo $idLabel, yellow('[EXIT]') . '   exited with status code ', (string) $status, PHP_EOL;
    });
}

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

function blue($str) {
    return "\033[34m{$str}\033[0m";
}
