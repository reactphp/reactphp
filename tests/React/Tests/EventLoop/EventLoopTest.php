<?php

namespace React\Tests\EventLoop;

use React\EventLoop\EventLoop;

class EventLoopTest extends AbstractLoopTest
{
    public function createLoop()
    {
        if (!class_exists('\EventBase')) {
            $this->markTestSkipped('libev tests skipped because ext-libev is not installed.');
        }
        
        return new EventLoop();
    }

    public function testLibEvConstructor()
    {
        $loop = new EventLoop();
    }
}
