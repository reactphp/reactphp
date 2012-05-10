<?php

namespace React\Tests\Socket;

use React\Socket\Buffer;

class BufferTest extends TestCase
{
    /**
     * @covers React\Socket\Buffer::__construct
     */
    public function testConstructor()
    {
        $socket = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $buffer = new Buffer($socket, $loop);
        $buffer->on('error', $this->expectCallableNever());
    }

    /**
     * @covers React\Socket\Buffer::write
     * @covers React\Socket\Buffer::handleWrite
     */
    public function testWrite()
    {
        $socket = fopen('php://temp', 'r+');
        $loop = $this->createWriteableLoopMock();

        $buffer = new Buffer($socket, $loop);
        $buffer->on('error', $this->expectCallableNever());

        $buffer->write("foobar\n");
        rewind($socket);
        $this->assertSame("foobar\n", fread($socket, 1024));
    }

    /**
     * @covers React\Socket\Buffer::end
     */
    public function testEnd()
    {
        $socket = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $buffer = new Buffer($socket, $loop);
        $buffer->on('error', $this->expectCallableNever());
        $buffer->on('end', $this->expectCallableOnce());

        $this->assertFalse($buffer->closed);
        $buffer->end();
        $this->assertTrue($buffer->closed);
    }

    /**
     * @covers React\Socket\Buffer::end
     */
    public function testError()
    {
        $socket = null;
        $loop = $this->createWriteableLoopMock();

        $error = null;

        $buffer = new Buffer($socket, $loop);
        $buffer->on('error', function ($message) use (&$error) {
            $error = $message;
        });

        $buffer->write('Attempting to write to bad socket');
        $this->assertInstanceOf('Exception', $error);
    }

    private function createWriteableLoopMock()
    {
        $loop = $this->createLoopMock();
        $loop
            ->expects($this->once())
            ->method('addWriteStream')
            ->will($this->returnCallback(function ($socket, $listener) {
                call_user_func($listener, $socket);
            }));

        return $loop;
    }

    private function createLoopMock()
    {
        return $this->getMock('React\EventLoop\LoopInterface');
    }
}
