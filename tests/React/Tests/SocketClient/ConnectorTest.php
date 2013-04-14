<?php

namespace React\Tests\SocketClient;

use React\EventLoop\StreamSelectLoop;
use React\Socket\Server;
use React\SocketClient\Connector;
use React\Tests\Socket\TestCase;

class ConnectorTest extends TestCase
{
    /** @test */
    public function connectionToEmptyPortShouldFail()
    {
        $loop = new StreamSelectLoop();

        $dns = $this->createResolverMock();

        $connector = new Connector($loop, $dns);
        $connector->create('127.0.0.1', 9999)
                ->then($this->expectCallableNever(), $this->expectCallableOnce());

        $loop->run();
    }

    /** @test */
    public function connectionToTcpServerShouldSucceed()
    {
        $capturedStream = null;

        $loop = new StreamSelectLoop();

        $server = new Server($loop);
        $server->on('connection', $this->expectCallableOnce());
        $server->on('connection', function () use ($server, $loop) {
            $server->shutdown();
        });
        $server->listen(9999);

        $dns = $this->createResolverMock();

        $connector = new Connector($loop, $dns);
        $connector->create('127.0.0.1', 9999)
                ->then(function ($stream) use (&$capturedStream) {
                    $capturedStream = $stream;
                    $stream->end();
                });

        $loop->run();

        $this->assertInstanceOf('React\Stream\Stream', $capturedStream);
    }

    private function createResolverMock()
    {
        return $this->getMockBuilder('React\Dns\Resolver\Resolver')
                    ->disableOriginalConstructor()
                    ->getMock();
    }
}
