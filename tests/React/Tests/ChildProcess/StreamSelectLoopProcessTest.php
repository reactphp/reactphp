<?php

namespace React\Tests\ChildProcess;

use React\EventLoop\StreamSelectLoop;

class StreamSelectLoopProcessTest extends AbstractProcessTest
{
    public function createLoop()
    {
        return new StreamSelectLoop();
    }
}
