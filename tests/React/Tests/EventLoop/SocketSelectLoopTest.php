<?php

namespace React\Tests\EventLoop;

use React\EventLoop\SocketSelectLoop;

class SocketSelectLoopTest extends AbstractLoopTest
{
    public function createLoop()
    {
        return new SocketSelectLoop();
    }

    public function testStreamSelectConstructor()
    {
        $loop = new SocketSelectLoop();
    }
}
