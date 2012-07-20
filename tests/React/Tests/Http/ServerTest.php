<?php

namespace React\Tests\Http;

use React\Http\Server;
use React\Tests\Socket\TestCase;
use React\Tests\Socket\Stub\ServerStub;
use React\Tests\Socket\Stub\ConnectionStub;

class ServerTest extends TestCase
{
    public function testRequestEventIsEmitted()
    {
        $io = new ServerStub();

        $server = new Server($io);
        $server->on('request', $this->expectCallableOnce());

        $conn = new ConnectionStub();
        $io->emit('connection', array($conn));

        $data = $this->createGetRequest();
        $conn->emit('data', array($data));
    }

    public function testRequestEvent()
    {
        $io = new ServerStub();

        $test = $this;
        $i = 0;

        $server = new Server($io);
        $server->on('request', function ($request, $response) use ($test, &$i) {
            $i++;

            $test->assertInstanceOf('React\Http\Request', $request);
            $test->assertSame('/', $request->getPath());
            $test->assertSame('GET', $request->getMethod());

            $test->assertInstanceOf('React\Http\Response', $response);
        });

        $conn = new ConnectionStub();
        $io->emit('connection', array($conn));

        $data = $this->createGetRequest();
        $conn->emit('data', array($data));

        $this->assertSame(1, $i);
    }

    public function testResponseContainsPoweredByHeader()
    {
        $io = new ServerStub();

        $server = new Server($io);
        $server->on('request', function ($request, $response) {
            $response->writeHead();
            $response->end();
        });

        $conn = new ConnectionStub();
        $io->emit('connection', array($conn));

        $data = $this->createGetRequest();
        $conn->emit('data', array($data));

        $this->assertContains("\r\nX-Powered-By: React/alpha\r\n", $conn->getData());
    }

    private function createGetRequest()
    {
        $data = "GET / HTTP/1.1\r\n";
        $data .= "Host: example.com:80\r\n";
        $data .= "Connection: close\r\n";
        $data .= "\r\n";

        return $data;
    }
}
