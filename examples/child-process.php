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
    echo $idLabel, '[PID]    pid is ', (string) $process->getPid(), PHP_EOL;
    echo $idLabel, '[CMD]    ', $process->getCommand(), PHP_EOL;

    $process->stdout->on('data', function ($data) use ($idLabel) {
        echo $idLabel, '[STDOUT] ';
        var_dump($data);
    });

    $process->stderr->on('data', function ($data) use ($idLabel) {
        echo $idLabel, '[STDERR] ';
        var_dump($data);
    });

    $process->on('exit', function ($status) use ($idLabel) {
        echo $idLabel, '[EXIT]   exited with status code ', (string) $status, PHP_EOL;
    });
}

$loop->run();
