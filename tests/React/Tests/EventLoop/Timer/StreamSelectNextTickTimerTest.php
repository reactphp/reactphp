<?php

namespace React\Tests\EventLoop\Timer;

use React\EventLoop\StreamSelectNextTickLoop;

class StreamSelectNextTickTimerTest extends AbstractTimerTest
{
    public function createLoop()
    {
        return new StreamSelectNextTickLoop;
    }
}
