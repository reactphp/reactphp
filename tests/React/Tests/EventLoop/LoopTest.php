<?php

namespace React\Tests\EventLoop;

use React\Tests\Socket\TestCase;
use React\EventLoop\StreamSelectLoop;

class LoopTest extends TestCase
{
    private function createLoop()
    {
        return new StreamSelectLoop();
    }

    /**
     * @covers React\EventLoop\StreamSelectLoop
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
