<?php

namespace React\Tests\EventLoop;

use React\EventLoop\LibUvLoop;

class LibUvEventLoopTest extends AbstractLoopTest
{
    public function createLoop()
    {
        if (!function_exists('uv_default_loop')) {
            $this->markTestSkipped('libuv tests skipped because ext-uv is not installed.');
        }

        return new LibUvLoop();
    }

    public function testLibEventConstructor()
    {
        $loop = new LibUvLoop();
    }
    
    public function stopShouldStopRunningLoop()
    {
        $this->fail('libuv does not currently provide a stop method');
    }
}
