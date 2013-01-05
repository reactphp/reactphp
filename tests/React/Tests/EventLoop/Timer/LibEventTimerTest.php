<?php

namespace React\Tests\EventLoop\Timer;

use React\EventLoop\LibEventLoop;

class LibEventTimerTest extends AbstractTimerTest
{
    public function createLoop()
    {
        if (!function_exists('event_base_new')) {
            $this->markTestSkipped('libevent tests skipped because ext-libevent is not installed.');
        }

        return new LibEventLoop();
    }
}
