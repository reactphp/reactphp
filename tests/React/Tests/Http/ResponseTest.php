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
            ->expects($this->at(4))
            ->method('write')
            ->with("5\r\nHello\r\n");
        $conn
            ->expects($this->at(5))
            ->method('write')
            ->with("1\r\n \r\n");
        $conn
            ->expects($this->at(6))
            ->method('write')
            ->with("6\r\nWorld\n\r\n");
        $conn
            ->expects($this->at(7))
            ->method('write')
            ->with("0\r\n\r\n");

        $response = new Response($conn);
        $response->writeHead();

        $response->write('Hello');
        $response->write(' ');
        $response->write("World\n");
        $response->end();
    }

    public function testResponseShouldEmitEndOnStreamEnd()
    {
        $ended = false;

        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $response = new Response($conn);

        $response->on('end', function () use (&$ended) {
            $ended = true;
        });
        $response->end();

        $this->assertTrue($ended);
    }

    /** @test */
    public function writeContinueShouldSendContinueLineBeforeRealHeaders()
    {
        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $conn
            ->expects($this->at(3))
            ->method('write')
            ->with("HTTP/1.1 100 Continue\r\n");
        $conn
            ->expects($this->at(4))
            ->method('write')
            ->with($this->stringContains("HTTP/1.1 200 OK\r\n"));

        $response = new Response($conn);
        $response->writeContinue();
        $response->writeHead();
    }

    /** @test */
    public function shouldForwardEndDrainAndErrorEvents()
    {
        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $conn
            ->expects($this->at(0))
            ->method('on')
            ->with('end', $this->isInstanceOf('Closure'));
        $conn
            ->expects($this->at(1))
            ->method('on')
            ->with('error', $this->isInstanceOf('Closure'));
        $conn
            ->expects($this->at(2))
            ->method('on')
            ->with('drain', $this->isInstanceOf('Closure'));

        $response = new Response($conn);
    }

    /** @test */
    public function shouldRemoveNewlinesFromHeaders()
    {
        $expected = '';
        $expected .= "HTTP/1.1 200 OK\r\n";
        $expected .= "X-Powered-By: React/alpha\r\n";
        $expected .= "FooBar: BazQux\r\n";
        $expected .= "Transfer-Encoding: chunked\r\n";
        $expected .= "\r\n";

        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $conn
            ->expects($this->once())
            ->method('write')
            ->with($expected);

        $response = new Response($conn);
        $response->writeHead(200, array("Foo\nBar" => "Baz\rQux"));
    }

    /** @test */
    public function missingStatusCodeTextShouldResultInNumberOnlyStatus()
    {
        $expected = '';
        $expected .= "HTTP/1.1 700 \r\n";
        $expected .= "X-Powered-By: React/alpha\r\n";
        $expected .= "Transfer-Encoding: chunked\r\n";
        $expected .= "\r\n";

        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $conn
            ->expects($this->once())
            ->method('write')
            ->with($expected);

        $response = new Response($conn);
        $response->writeHead(700);
    }

    /** @test */
    public function shouldAllowArrayHeaderValues()
    {
        $expected = '';
        $expected .= "HTTP/1.1 200 OK\r\n";
        $expected .= "X-Powered-By: React/alpha\r\n";
        $expected .= "Set-Cookie: foo=bar\r\n";
        $expected .= "Set-Cookie: bar=baz\r\n";
        $expected .= "Transfer-Encoding: chunked\r\n";
        $expected .= "\r\n";

        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $conn
            ->expects($this->once())
            ->method('write')
            ->with($expected);

        $response = new Response($conn);
        $response->writeHead(200, array("Set-Cookie" => array("foo=bar", "bar=baz")));
    }

    /** @test */
    public function shouldIgnoreHeadersWithNullValues()
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
        $response->writeHead(200, array("FooBar" => null));
    }
}
