<?php

namespace React\Tests\EventLoop;

use React\EventLoop\LibEvLoop;

class LibEvLoopTest extends AbstractLoopTest
{
    public function createLoop()
    {
        if (!class_exists('libev\EventLoop')) {
            $this->markTestSkipped('libev tests skipped because ext-libev is not installed.');
        }

        return new LibEvLoop();
    }

    public function testLibEvConstructor()
    {
        $loop = new LibEvLoop();
    }
}
