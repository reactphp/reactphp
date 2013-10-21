<?php

namespace React\Tests\EventLoop;

use React\EventLoop\EventLoop;

class EventLoopTest extends AbstractLoopTest
{
    public function createLoop()
    {
        if (!class_exists('\EventBase')) {
            $this->markTestSkipped('event tests skipped because pecl/event is not installed.');
        }
        
        return new EventLoop();
    }

    public function testEventConstructor()
    {
        $loop = new EventLoop();
    }
}
