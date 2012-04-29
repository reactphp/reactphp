<?php

namespace Igorw\Tests\SocketServer;

use Igorw\SocketServer\Connection;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Igorw\SocketServer\Connection::__construct
     */
    public function testConstructor()
    {
        $socket = fopen('php://temp', 'r+');
        $server = $this->createServerMock();

        $conn = new Connection($socket, $server);
    }

    /**
     * @covers Igorw\SocketServer\Connection::isOpen
     */
    public function testIsOpen()
    {
        $socket = fopen('php://temp', 'r+');
        $server = $this->createServerMock();

        $conn = new Connection($socket, $server);
        $this->assertTrue($conn->isOpen());

        fclose($socket);
        $this->assertFalse($conn->isOpen());
    }

    /**
     * @covers Igorw\SocketServer\Connection::write
     */
    public function testWrite()
    {
        $socket = fopen('php://temp', 'r+');
        $server = $this->createServerMock();

        $conn = new Connection($socket, $server);
        $conn->write("foo\n");

        rewind($socket);
        $this->assertSame("foo\n", fgets($socket));
    }

    /**
     * @covers Igorw\SocketServer\Connection::write
     */
    public function testWriteError()
    {
        $socket = "Silly developer, you can't write to to a string!";
        $server = $this->createServerMock();

        $conn = new Connection($socket, $server);
        $error = false;
        $conn->on('error', function() use (&$error) {
            $error = true;
        });
        $conn->write('Attempting to write to a string');

        $this->assertTrue($error);
    }

    /**
     * @covers Igorw\SocketServer\Connection::close
     */
    public function testClose()
    {
        $socket = fopen('php://temp', 'r+');

        $server = $this->createServerMock();
        $server
            ->expects($this->once())
            ->method('close');

        $conn = new Connection($socket, $server);
        $conn->close();
    }

    private function createServerMock()
    {
        $mock = $this->getMockBuilder('Igorw\SocketServer\Server')
            ->disableOriginalConstructor()
            ->getMock();

        return $mock;
    }
}
