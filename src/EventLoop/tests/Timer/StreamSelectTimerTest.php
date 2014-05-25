<?php

namespace React\Tests\EventLoop\Timer;

use React\EventLoop\StreamSelectLoop;

class StreamSelectTimerTest extends AbstractTimerTest
{
    public function createLoop()
    {
        return new StreamSelectLoop();
    }
}
