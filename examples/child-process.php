<?php

    require __DIR__ . '/../vendor/autoload.php';

    $loop = React\EventLoop\Factory::create();

    $process = new React\ChildProcess\Process('php child-child-bitch.php');
    $process->start($loop);

    $process->on('exit', function($exitCode, $termSignal) {
        echo "Child exit()\n";
    });

    $loop->addTimer(0.001, function(React\EventLoop\Timer\Timer $timer) use ($process) {
        $process->stdout->on('data', function($output) {
            echo "child bitch says what?: {$output}";
        });
    });

    $loop->addPeriodicTimer(5, function($timer) {
        echo "Parent can not be blocked by puny child!\n";
    });

    $loop->run();

    echo "Parent is done\n";
