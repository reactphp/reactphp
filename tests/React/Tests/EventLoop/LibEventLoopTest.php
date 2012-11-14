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

        if (!function_exists('event_base_new')) {
            $this->markTestSkipped('libevent tests skipped because ext-libevent is not installed.');
        }

        return new LibEventLoop();
    }

    public function testLibEventConstructor()
    {
        $loop = new LibEventLoop();
    }
}
