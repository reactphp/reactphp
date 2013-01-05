<?php

namespace React\Tests\EventLoop;

use React\EventLoop\StreamSelectLoop;

class StreamSelectLoopTest extends AbstractLoopTest
{
    public function createLoop()
    {
        return new StreamSelectLoop();
    }

    public function testStreamSelectConstructor()
    {
        $loop = new StreamSelectLoop();
    }
}
