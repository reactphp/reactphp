<?php

namespace React\Tests\SocketClient;

use React\EventLoop\StreamSelectLoop;
use React\Socket\Server;
use React\SocketClient\ConnectionManager;
use React\Tests\Socket\TestCase;

class ConnectionManagerTest extends TestCase
{
    /** @test */
    public function connectionToEmptyPortShouldFail()
    {
        $loop = new StreamSelectLoop();

        $dns = $this->createResolverMock();

        $manager = new ConnectionManager($loop, $dns);
        $manager->getConnection('127.0.0.1', 9999)
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

        $manager = new ConnectionManager($loop, $dns);
        $manager->getConnection('127.0.0.1', 9999)
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
