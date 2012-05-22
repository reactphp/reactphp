<?php

namespace React\Tests\Socket;

use React\EventLoop\StreamSelectLoop;

class LoopTest extends TestCase
{
    private function createLoop()
    {
        return new StreamSelectLoop(0);
    }

    /**
     * @covers React\EventLoop\StreamSelectLoop::tick
     * @covers React\EventLoop\StreamSelectLoop::addReadStream
     */
    public function testAddReadStream()
    {
        $loop = $this->createLoop();

        $input = fopen('php://temp', 'r+');

        $loop->addReadStream($input, $this->expectCallableOnce());

        fwrite($input, "foo\n");
        rewind($input);
        $loop->tick();
    }
}
