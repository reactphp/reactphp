<?php

namespace React\Tests\ChildProcess;

use React\EventLoop\LibEvLoop;

class LibEvLoopProcessTest extends AbstractProcessTest
{
    public function createLoop()
    {
        if (!class_exists('libev\EventLoop')) {
            $this->markTestSkipped('libev tests skipped because ext-libev is not installed.');
        }

        return new LibEvLoop();
    }
}
