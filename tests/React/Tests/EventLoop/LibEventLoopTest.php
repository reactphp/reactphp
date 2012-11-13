<?php

namespace React\Tests\EventLoop;

use React\EventLoop\LoopInterface;
use React\EventLoop\LibEventLoop;

class LibEventLoopTest extends AbstractLoopTest
{
    public function createLoop()
    {
        if (getenv('TRAVIS')) {
            $this->markTestSkipped('libevent tests skipped on travis due to linux epoll issues.');
        }

        return new LibEventLoop();
    }

    public function testLibEventConstructor()
    {
        $loop = new LibEventLoop();
    }
}
