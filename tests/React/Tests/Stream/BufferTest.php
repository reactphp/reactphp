<?php

namespace React\Tests\Stream;

use React\Stream\Buffer;
use React\Tests\Socket\TestCase;

class BufferTest extends TestCase
{
    /**
     * @covers React\Stream\Buffer::__construct
     */
    public function testConstructor()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $buffer = new Buffer($stream, $loop);
        $buffer->on('error', $this->expectCallableNever());
    }

    /**
     * @covers React\Stream\Buffer::write
     * @covers React\Stream\Buffer::handleWrite
     */
    public function testWrite()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createWriteableLoopMock();

        $buffer = new Buffer($stream, $loop);
        $buffer->on('error', $this->expectCallableNever());

        $buffer->write("foobar\n");
        rewind($stream);
        $this->assertSame("foobar\n", fread($stream, 1024));
    }

    /**
     * @covers React\Stream\Buffer::end
     */
    public function testClose()
    {
        $stream = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $buffer = new Buffer($stream, $loop);
        $buffer->on('error', $this->expectCallableNever());
        $buffer->on('close', $this->expectCallableOnce());

        $this->assertFalse($buffer->closed);
        $buffer->end();
        $this->assertTrue($buffer->closed);
    }

    /**
     * @covers React\Stream\Buffer::end
     */
    public function testError()
    {
        $stream = null;
        $loop = $this->createWriteableLoopMock();

        $error = null;

        $buffer = new Buffer($stream, $loop);
        $buffer->on('error', function ($message) use (&$error) {
            $error = $message;
        });

        $buffer->write('Attempting to write to bad stream');
        $this->assertInstanceOf('Exception', $error);
        $this->assertSame('fwrite() expects parameter 1 to be resource, null given', $error->getMessage());
    }

    private function createWriteableLoopMock()
    {
        $loop = $this->createLoopMock();
        $loop
            ->expects($this->any())
            ->method('addWriteStream')
            ->will($this->returnCallback(function ($stream, $listener) {
                call_user_func($listener, $stream);
            }));

        return $loop;
    }

    private function createLoopMock()
    {
        return $this->getMock('React\EventLoop\LoopInterface');
    }
}
