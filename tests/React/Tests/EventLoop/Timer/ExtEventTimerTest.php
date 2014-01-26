<?php

namespace React\Tests\EventLoop\Timer;

use React\EventLoop\ExtEventLoop;

class ExtEventTimerTest extends AbstractTimerTest
{
    public function createLoop()
    {
        if (!extension_loaded('event')) {
            $this->markTestSkipped('ext-event tests skipped because ext-event is not installed.');
        }

        return new ExtEventLoop();
    }
}
