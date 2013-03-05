<?php

namespace React\Tests\EventLoop\Timer;

use React\EventLoop\LibUvLoop;

class LibUvTimerTest extends AbstractTimerTest
{
    public function setUp()
    {
        
    }
    
    public function createLoop()
    {
        if (!function_exists('uv_default_loop')) {
            $this->markTestSkipped('libuv tests skipped because ext-uv is not installed.');
        }

        return new LibUvLoop();
    }
}
