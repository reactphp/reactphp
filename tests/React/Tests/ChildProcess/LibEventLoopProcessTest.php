<?php

namespace React\Tests\ChildProcess;

use React\EventLoop\LibEventLoop;

class LibEventLoopProcessTest extends AbstractProcessTest
{
    public function createLoop()
    {
        if (!function_exists('event_base_new')) {
            $this->markTestSkipped('libevent tests skipped because ext-libevent is not installed.');
        }

        return new LibEventLoop();
    }
}
