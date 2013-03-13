<?php

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/bench.php';

$x = 1000;

if (extension_loaded('xdebug')) {
    echo "Warning: xdebug is loaded, it can impact performance negatively.\n";
    echo "\n";
}

$tests = array(
    $x . ' one-off timers' => function ($loop) use ($x) {
        for ($i = 0; $i < $x; $i++) {
            $loop->addTimer(1, function ($signature, $loop) {});
        }
        $loop->run();
    },
    $x . ' periodic timers' => function ($loop) use ($x) {
        for ($i = 0; $i < $x; $i++) {
            $loop->addPeriodicTimer(2, function ($signature, $loop) use (&$i, $x) {
                if ($i >= $x) {
                    $loop->cancelTimer($signature);
                }
            });
        }
        $loop->run();
    },
);

benchLoops($tests);
