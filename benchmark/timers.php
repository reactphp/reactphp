<?php

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/bench.php';

$tests = array(
    '1000 one-off timers' => function ($loop) {
        for ($i = 0; $i < 1000; $i++) {
            $loop->addTimer(1, function ($signature, $loop) {});
        }
        $loop->run();
    },
    '1000 periodic timers' => function ($loop) {
        for ($i = 0; $i < 1000; $i++) {
            $loop->addPeriodicTimer(2, function ($signature, $loop) use (&$i) {
                if ($i >= 1000) {
                    $loop->cancelTimer($signature);
                }
            });
        }
        $loop->run();
    },
);

benchLoops($tests);
