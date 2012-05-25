<?php

namespace React\Tests\Http;

use React\Http\Response;
use React\Tests\Socket\TestCase;

class ResponseTest extends TestCase
{
    public function testResponseShouldBeChunkedByDefault()
    {
        $expected = '';
        $expected .= "HTTP/1.1 200 OK\r\n";
        $expected .= "X-Powered-By: React/alpha\r\n";
        $expected .= "Transfer-Encoding: chunked\r\n";
        $expected .= "\r\n";

        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $conn
            ->expects($this->once())
            ->method('write')
            ->with($expected);

        $response = new Response($conn);
        $response->writeHead();
    }

    public function testResponseShouldNotBeChunkedWithContentLength()
    {
        $expected = '';
        $expected .= "HTTP/1.1 200 OK\r\n";
        $expected .= "X-Powered-By: React/alpha\r\n";
        $expected .= "Content-Length: 22\r\n";
        $expected .= "\r\n";

        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $conn
            ->expects($this->once())
            ->method('write')
            ->with($expected);

        $response = new Response($conn);
        $response->writeHead(200, array('Content-Length' => 22));
    }

    public function testResponseBodyShouldBeChunkedCorrectly()
    {
        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $conn
            ->expects($this->at(1))
            ->method('write')
            ->with("5\r\nHello\r\n");
        $conn
            ->expects($this->at(2))
            ->method('write')
            ->with("1\r\n \r\n");
        $conn
            ->expects($this->at(3))
            ->method('write')
            ->with("6\r\nWorld\n\r\n");
        $conn
            ->expects($this->at(4))
            ->method('write')
            ->with("0\r\n\r\n");

        $response = new Response($conn);
        $response->writeHead();

        $response->write('Hello');
        $response->write(' ');
        $response->write("World\n");
        $response->end();
    }
}
