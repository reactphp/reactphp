<?php

namespace React\Tests\EventLoop;

use React\EventLoop\EvLoop;

class EvLoopTest extends AbstractLoopTest
{
    public function createLoop()
    {
        if (!class_exists('\EvLoop')) {
            $this->markTestSkipped('ev tests skipped because pecl/ev is not installed.');
        }

        return new EvLoop();
    }

    public function testEvConstructor()
    {
        $loop = new EvLoop();
    }
}
