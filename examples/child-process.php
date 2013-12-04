<?php

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$process = new React\ChildProcess\Process('php child-child.php');

$process->on('exit', function($exitCode, $termSignal) {
    echo "Child exit\n";
});

$loop->addTimer(0.001, function($timer) use ($process) {
    $process->start($timer->getLoop());

    $process->stdout->on('data', function($output) {
        echo "Child script says: {$output}";
    });
});

$loop->addPeriodicTimer(5, function($timer) {
    echo "Parent cannot be blocked by child\n";
});

$loop->run();
