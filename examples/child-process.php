<?php

    require __DIR__ . '/../vendor/autoload.php';

    $loop = React\EventLoop\Factory::create();

    $process = new React\ChildProcess\Process('php child-child-bitch.php');

    $process->on('exit', function($exitCode, $termSignal) {
        echo "Child exit()\n";
    });

    $loop->addTimer(0.001, function($timer) use ($process) {
        $process->start($timer->getLoop());

        $process->stdout->on('data', function($output) {
            echo "child bitch says what?: {$output}";
        });
    });

    $loop->addPeriodicTimer(5, function($timer) {
        echo "Parent can not be blocked by puny child!\n";
    });

    $loop->run();

    echo "Parent is done\n";
