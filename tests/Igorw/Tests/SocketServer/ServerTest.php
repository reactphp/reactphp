<?php

namespace Igorw\Tests\SocketServer;

use Igorw\SocketServer\Server;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    private $server;
    private $port;

    /**
     * @covers Igorw\SocketServer\Server::__construct
     * @covers Igorw\SocketServer\Server::getPort
     */
    public function setUp()
    {
        $this->server = new Server('localhost', 0, 0);

        $this->port = $this->server->getPort();
    }

    /**
     * @covers Igorw\SocketServer\Server::tick
     * @covers Igorw\SocketServer\Server::handleConnection
     * @covers Igorw\SocketServer\Server::createConnection
     */
    public function testConnection()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        $this->server->on('connect', $this->expectCallableOnce());
        $this->server->tick();
    }

    /**
     * @covers Igorw\SocketServer\Server::tick
     * @covers Igorw\SocketServer\Server::handleConnection
     * @covers Igorw\SocketServer\Server::createConnection
     */
    public function testConnectionWithManyClients()
    {
        $client1 = stream_socket_client('tcp://localhost:'.$this->port);
        $client2 = stream_socket_client('tcp://localhost:'.$this->port);
        $client3 = stream_socket_client('tcp://localhost:'.$this->port);

        $this->server->on('connect', $this->expectCallableExactly(3));
        $this->server->tick();
        $this->server->tick();
        $this->server->tick();
    }

    /**
     * @covers Igorw\SocketServer\Server::tick
     * @covers Igorw\SocketServer\Server::handleData
     */
    public function testDataWithNoData()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        $mock = $this->expectCallableNever();

        $this->server->on('connect', function ($conn) use ($mock) {
            $conn->on('data', $mock);
        });
        $this->server->tick();
        $this->server->tick();
    }

    /**
     * @covers Igorw\SocketServer\Server::tick
     * @covers Igorw\SocketServer\Server::handleData
     */
    public function testData()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        fwrite($client, "foo\n");

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with("foo\n");

        $this->server->on('connect', function ($conn) use ($mock) {
            $conn->on('data', $mock);
        });
        $this->server->tick();
        $this->server->tick();
    }

    /**
     * @covers Igorw\SocketServer\Server::tick
     * @covers Igorw\SocketServer\Server::handleDisconnect
     */
    public function testDisconnectWithoutDisconnect()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        $mock = $this->expectCallableNever();

        $this->server->on('connect', function ($conn) use ($mock) {
            $conn->on('end', $mock);
        });
        $this->server->tick();
        $this->server->tick();
    }

    /**
     * @covers Igorw\SocketServer\Server::tick
     * @covers Igorw\SocketServer\Server::handleDisconnect
     * @covers Igorw\SocketServer\Server::close
     */
    public function testDisconnect()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        fclose($client);

        $mock = $this->expectCallableOnce();

        $this->server->on('connect', function ($conn) use ($mock) {
            $conn->on('end', $mock);
        });
        $this->server->tick();
        $this->server->tick();
    }

    /**
     * @covers Igorw\SocketServer\Server::tick
     * @covers Igorw\SocketServer\Server::write
     */
    public function testWrite()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);
        $this->server->tick();

        $this->server->write("foo\n");
        $this->server->tick();

        $this->assertEquals("foo\n", fgets($client));
    }

    /**
     * @covers Igorw\SocketServer\Server::tick
     * @covers Igorw\SocketServer\Server::addInput
     * @covers Igorw\SocketServer\Server::handleInput
     */
    public function testInput()
    {
        $input = fopen('php://temp', 'r+');

        $this->server = new Server('localhost', 0, 0);

        $this->server->addInput('foo', $input);

        $this->server->on('input.foo', $this->expectCallableOnce());
        $this->server->on('input.bar', $this->expectCallableNever());

        fwrite($input, "foo\n");
        $this->server->tick();
    }

    /**
     * @covers Igorw\SocketServer\Server::tick
     * @covers Igorw\SocketServer\Server::close
     */
    public function testClose()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);
        $this->server->tick();

        $this->assertCount(1, $this->server->getClients());

        $conns = $this->server->getClients();
        list($key, $conn) = each($conns);

        $conn->close();

        $this->assertCount(0, $this->server->getClients());
    }

    /**
     * @covers Igorw\SocketServer\Server::tick
     * @covers Igorw\SocketServer\Server::getClients
     */
    public function testGetClients()
    {
        $this->assertCount(0, $this->server->getClients());

        $client = stream_socket_client('tcp://localhost:'.$this->port);
        $this->server->tick();

        $this->assertCount(1, $this->server->getClients());
    }

    /**
     * @covers Igorw\SocketServer\Server::tick
     * @covers Igorw\SocketServer\Server::getClient
     */
    public function testGetClient()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);
        $this->server->tick();

        $conns = $this->server->getClients();
        list($key, $conn) = each($conns);

        $this->assertInstanceOf('Igorw\SocketServer\Connection', $conn);
        $this->assertSame($conn, $this->server->getClient($key));
    }

    /**
     * @covers Igorw\SocketServer\Server::shutdown
     */
    public function tearDown()
    {
        $this->server->shutdown();
    }

    private function expectCallableExactly($amount)
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->exactly($amount))
            ->method('__invoke');

        return $mock;
    }

    private function expectCallableOnce()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke');

        return $mock;
    }

    private function expectCallableNever()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->never())
            ->method('__invoke');

        return $mock;
    }

    private function createCallableMock()
    {
        return $this->getMock('Igorw\Tests\SocketServer\CallableMock');
    }
}
