<?php

/*
This example shows how nextTick and futureTick events are scheduled for
execution.

The expected output is:

    next-tick #1
    next-tick #2
    future-tick #1
    timer
    future-tick #2

Note that both nextTick and futureTick events are executed before timer and I/O
events on each tick.

nextTick events registered inside an existing nextTick handler are guaranteed
to be executed before timer and I/O handlers are processed, whereas futureTick
handlers are always deferred.
*/

require __DIR__.'/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$loop->addTimer(
    0,
    function () {
        echo 'timer' . PHP_EOL;
    }
);

$loop->nextTick(
    function ($loop) {
        echo 'next-tick #1' . PHP_EOL;

        $loop->nextTick(
            function () {
                echo 'next-tick #2' . PHP_EOL;
            }
        );
    }
);

$loop->futureTick(
    function ($loop) {
        echo 'future-tick #1' . PHP_EOL;

        $loop->futureTick(
            function () {
                echo 'future-tick #2' . PHP_EOL;
            }
        );
    }
);

$loop->run();
