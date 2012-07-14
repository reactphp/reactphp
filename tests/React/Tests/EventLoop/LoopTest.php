<?php

namespace React\Tests\EventLoop;

use React\Tests\Socket\TestCase;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\EventLoop\LibEventLoop;
use React\EventLoop\LibEvLoop;

class LoopTest extends TestCase
{
    public function provideLoop()
    {
        return array(
            array(new StreamSelectLoop()),
            array(new LibEventLoop()),
            array(new LibEvLoop()),
        );
    }

    public function testStreamSelectConstructor()
    {
        $loop = new StreamSelectLoop();
    }

    public function testLibEventConstructor()
    {
        $loop = new LibEventLoop();
    }

    public function testLibEvConstructor()
    {
        $loop = new LibEvLoop();
    }

    /**
     * @dataProvider provideLoop
     */
    public function testAddReadStream(LoopInterface $loop)
    {
        $input = fopen('php://temp', 'r+');

        $loop->addReadStream($input, $this->expectCallableExactly(2));

        fwrite($input, "foo\n");
        rewind($input);
        $loop->tick();

        fwrite($input, "bar\n");
        rewind($input);
        $loop->tick();
    }

    /**
     * @dataProvider provideLoop
     */
    public function testAddWriteStream(LoopInterface $loop)
    {
        $input = fopen('php://temp', 'r+');

        $loop->addWriteStream($input, $this->expectCallableExactly(2));
        $loop->tick();
        $loop->tick();
    }

    /**
     * @dataProvider provideLoop
     */
    public function testRemoveReadStreamInstantly(LoopInterface $loop)
    {
        $input = fopen('php://temp', 'r+');

        $loop->addReadStream($input, $this->expectCallableNever());
        $loop->removeReadStream($input);

        fwrite($input, "bar\n");
        rewind($input);
        $loop->tick();
    }

    /**
     * @dataProvider provideLoop
     */
    public function testRemoveReadStreamAfterReading(LoopInterface $loop)
    {
        $input = fopen('php://temp', 'r+');

        $loop->addReadStream($input, $this->expectCallableOnce());

        fwrite($input, "foo\n");
        rewind($input);
        $loop->tick();

        $loop->removeReadStream($input);

        fwrite($input, "bar\n");
        rewind($input);
        $loop->tick();
    }

    /**
     * @dataProvider provideLoop
     */
    public function testRemoveWriteStreamInstantly(LoopInterface $loop)
    {
        $input = fopen('php://temp', 'r+');

        $loop->addWriteStream($input, $this->expectCallableNever());
        $loop->removeWriteStream($input);
        $loop->tick();
    }

    /**
     * @dataProvider provideLoop
     */
    public function testRemoveWriteStreamAfterWriting(LoopInterface $loop)
    {
        $input = fopen('php://temp', 'r+');

        $loop->addWriteStream($input, $this->expectCallableOnce());
        $loop->tick();

        $loop->removeWriteStream($input);
        $loop->tick();
    }

    /**
     * @dataProvider provideLoop
     */
    public function testRemoveStreamInstantly(LoopInterface $loop)
    {
        $input = fopen('php://temp', 'r+');

        $loop->addReadStream($input, $this->expectCallableNever());
        $loop->addWriteStream($input, $this->expectCallableNever());
        $loop->removeStream($input);

        fwrite($input, "bar\n");
        rewind($input);
        $loop->tick();
    }

    /**
     * @dataProvider provideLoop
     */
    public function testRemoveStream(LoopInterface $loop)
    {
        $input = fopen('php://temp', 'r+');

        $loop->addReadStream($input, $this->expectCallableOnce());
        $loop->addWriteStream($input, $this->expectCallableOnce());

        fwrite($input, "bar\n");
        rewind($input);
        $loop->tick();

        $loop->removeStream($input);

        fwrite($input, "bar\n");
        rewind($input);
        $loop->tick();
    }
}
