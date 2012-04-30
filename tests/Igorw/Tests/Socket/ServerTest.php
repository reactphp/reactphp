<?php

namespace Igorw\Tests\Socket;

use Igorw\Socket\Server;
use Igorw\EventLoop\StreamSelectLoop;

class ServerTest extends TestCase
{
    private $loop;
    private $server;
    private $port;

    private function createLoop()
    {
        return new StreamSelectLoop(0);
    }

    /**
     * @covers Igorw\Socket\Server::__construct
     * @covers Igorw\Socket\Server::getPort
     */
    public function setUp()
    {
        $this->loop = $this->createLoop();
        $this->server = new Server('localhost', 0, $this->loop);

        $this->port = $this->server->getPort();
    }

    /**
     * @covers Igorw\EventLoop\StreamSelectLoop::tick
     * @covers Igorw\Socket\Server::handleConnection
     * @covers Igorw\Socket\Server::createConnection
     */
    public function testConnection()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        $this->server->on('connect', $this->expectCallableOnce());
        $this->loop->tick();
    }

    /**
     * @covers Igorw\EventLoop\StreamSelectLoop::tick
     * @covers Igorw\Socket\Server::handleConnection
     * @covers Igorw\Socket\Server::createConnection
     */
    public function testConnectionWithManyClients()
    {
        $client1 = stream_socket_client('tcp://localhost:'.$this->port);
        $client2 = stream_socket_client('tcp://localhost:'.$this->port);
        $client3 = stream_socket_client('tcp://localhost:'.$this->port);

        $this->server->on('connect', $this->expectCallableExactly(3));
        $this->loop->tick();
        $this->loop->tick();
        $this->loop->tick();
    }

    /**
     * @covers Igorw\EventLoop\StreamSelectLoop::tick
     * @covers Igorw\Socket\Server::handleData
     */
    public function testDataWithNoData()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        $mock = $this->expectCallableNever();

        $this->server->on('connect', function ($conn) use ($mock) {
            $conn->on('data', $mock);
        });
        $this->loop->tick();
        $this->loop->tick();
    }

    /**
     * @covers Igorw\EventLoop\StreamSelectLoop::tick
     * @covers Igorw\Socket\Server::handleData
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
        $this->loop->tick();
        $this->loop->tick();
    }

    public function testFragmentedMessage()
    {
        $client = stream_socket_client('tcp://localhost:' . $this->port);
        $this->server->bufferSize = 2;

        fwrite($client, "Hello World!\n");

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with("He");

        $this->server->on('connect', function ($conn) use ($mock) {
            $conn->on('data', $mock);
        });
        $this->loop->tick();
        $this->loop->tick();
    }

    /**
     * @covers Igorw\EventLoop\StreamSelectLoop::tick
     * @covers Igorw\Socket\Server::handleDisconnect
     */
    public function testDisconnectWithoutDisconnect()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        $mock = $this->expectCallableNever();

        $this->server->on('connect', function ($conn) use ($mock) {
            $conn->on('end', $mock);
        });
        $this->loop->tick();
        $this->loop->tick();
    }

    /**
     * @covers Igorw\EventLoop\StreamSelectLoop::tick
     * @covers Igorw\Socket\Server::handleDisconnect
     * @covers Igorw\Socket\Server::close
     */
    public function testDisconnect()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        fclose($client);

        $mock = $this->expectCallableOnce();

        $this->server->on('connect', function ($conn) use ($mock) {
            $conn->on('end', $mock);
        });
        $this->loop->tick();
        $this->loop->tick();
    }

    /**
     * @covers Igorw\EventLoop\StreamSelectLoop::tick
     * @covers Igorw\Socket\Server::write
     */
    public function testWrite()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);
        $this->loop->tick();

        $this->server->write("foo\n");
        $this->loop->tick();

        $this->assertEquals("foo\n", fgets($client));
    }

    /**
     * @covers Igorw\EventLoop\StreamSelectLoop::tick
     * @covers Igorw\Socket\Server::addInput
     */
    public function testInput()
    {
        $input = fopen('php://temp', 'r+');

        $this->server->addInput('foo', $input);

        $this->server->on('input.foo', $this->expectCallableOnce());
        $this->server->on('input.bar', $this->expectCallableNever());

        fwrite($input, "foo\n");
        rewind($input);
        $this->loop->tick();
    }

    /**
     * @covers Igorw\EventLoop\StreamSelectLoop::tick
     * @covers Igorw\Socket\Server::close
     */
    public function testClose()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);
        $this->loop->tick();

        $this->assertCount(1, $this->server->getClients());

        $conns = $this->server->getClients();
        list($key, $conn) = each($conns);

        $conn->close();

        $this->assertCount(0, $this->server->getClients());
    }

    /**
     * @covers Igorw\EventLoop\StreamSelectLoop::tick
     * @covers Igorw\Socket\Server::getClients
     */
    public function testGetClients()
    {
        $this->assertCount(0, $this->server->getClients());

        $client = stream_socket_client('tcp://localhost:'.$this->port);
        $this->loop->tick();

        $this->assertCount(1, $this->server->getClients());
    }

    /**
     * @covers Igorw\EventLoop\StreamSelectLoop::tick
     * @covers Igorw\Socket\Server::getClient
     */
    public function testGetClient()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);
        $this->loop->tick();

        $conns = $this->server->getClients();
        list($key, $conn) = each($conns);

        $this->assertInstanceOf('Igorw\Socket\Connection', $conn);
        $this->assertSame($conn, $this->server->getClient($key));
    }

    /**
     * @covers Igorw\Socket\Server::shutdown
     */
    public function tearDown()
    {
        if ($this->server) {
            $this->server->shutdown();
        }
    }
}
