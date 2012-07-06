<?php

namespace React\Tests\Socket;

use React\Socket\Connection;
use React\Socket\Server;
use React\EventLoop\StreamSelectLoop;

class ConnectionTest extends TestCase
{
    /**
     * @covers React\Socket\Connection::__construct
     */
    public function testConstructor()
    {
        $socket = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $conn = new Connection($socket, $loop);
    }

    /**
     * @covers React\Socket\Connection::write
     */
    public function testWrite()
    {
        $socket = fopen('php://temp', 'r+');
        $loop = $this->createWriteableLoopMock();

        $conn = new Connection($socket, $loop);
        $conn->write("foo\n");

        rewind($socket);
        $this->assertSame("foo\n", fgets($socket));
    }

    /**
     * @covers React\Socket\Connection::write
     */
    public function testWriteError()
    {
        $socket = "Silly developer, you can't write to to a string!";
        $loop = $this->createWriteableLoopMock();

        $conn = new Connection($socket, $loop);
        $conn->on('error', $this->expectCallableOnce());
        $conn->write('Attempting to write to a string');
    }

    /**
     * @covers React\Socket\Connection::end
     */
    public function testEnd()
    {
        $socket = fopen('php://temp', 'r+');
        $loop = $this->createLoopMock();

        $conn = new Connection($socket, $loop);
        $conn->end();

        $this->assertFalse(is_resource($socket));
    }

    /**
     * @covers React\Socket\Connection::getRemoteAddress
     */
    public function testGetRemoteAddress()
    {
        $loop   = new StreamSelectLoop();
        $server = new Server($loop);
        $server->listen(0);

        $class  = new \ReflectionClass('React\\Socket\\Server');
        $master = $class->getProperty('master');
        $master->setAccessible(true);

        $client = stream_socket_client('tcp://localhost:' . $server->getPort());

        $class  = new \ReflectionClass('React\\Socket\\Connection');
        $method = $class->getMethod('parseAddress');
        $method->setAccessible(true);

        $servConn = new Connection($server, $loop);

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($method->invokeArgs($servConn, array(stream_socket_get_name($master->getValue($server), false))))
        ;

        $server->on('connection', function ($conn) use ($mock) {
            $mock($conn->getRemoteAddress());
        });
        $loop->tick();
    }

    public function remoteAddressProvider()
    {
        return array(
            array('192.168.1.120', '192.168.1.120:12345')
          , array('9999:0000:aaaa:bbbb:cccc:dddd:eeee:ffff', '[9999:0000:aaaa:bbbb:cccc:dddd:eeee:ffff]:12345')
          , array('10.0.0.1', '10.0.0.1:80')
        );
    }

    /**
     * @dataProvider remoteAddressProvider
     * @covers React\Socket\Connection::parseAddress
     */
    public function testParseAddress($expected, $given)
    {
        $class  = new \ReflectionClass('React\\Socket\\Connection');
        $method = $class->getMethod('parseAddress');
        $method->setAccessible(true);

        $socket = fopen('php://temp', 'r');
        $loop   = $this->createLoopMock();

        $conn = new Connection($socket, $loop);
        $result = $method->invokeArgs($conn, array($given));

        $this->assertEquals($expected, $result);
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
