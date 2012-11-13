<?php

namespace React\Tests\EventLoop;

use React\EventLoop\LoopInterface;
use React\EventLoop\LibEvLoop;

class LibEvLoopTest extends AbstractLoopTest
{
    public function createLoop()
    {
        return new LibEvLoop();
    }

    public function testLibEvConstructor()
    {
        $loop = new LibEvLoop();
    }
}
