<?php

namespace React\Tests\Http;

use React\Http\RequestParser;
use React\Tests\Socket\TestCase;

class RequestParserTest extends TestCase
{
    public function testSplitShouldHappenOnDoubleCrlf()
    {
        $parser = new RequestParser();
        $parser->on('request', $this->expectCallableNever());

        $parser->write("GET / HTTP/1.1\r\n");
        $parser->write("Host: example.com:80\r\n");
        $parser->write("Connection: close\r\n");

        $parser->removeAllListeners();
        $parser->on('request', $this->expectCallableOnce());

        $parser->write("\r\n");
    }

    public function testFeedInOneGo()
    {
        $parser = new RequestParser();
        $parser->on('request', $this->expectCallableOnce());

        $data = $this->createGetRequest();
        $parser->write($data);
    }

    public function testHeadersEventShouldReturnRequest()
    {
        $request = null;

        $parser = new RequestParser();
        $parser->on('request', function ($parsedRequest) use (&$request) {
            $request = $parsedRequest;
        });

        $data = $this->createGetRequest();
        $parser->write($data);

        $this->assertInstanceOf('React\Http\Request', $request);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/', $request->getPath());
        $this->assertSame(array(), $request->getQuery());
        $this->assertSame('1.1', $request->getHttpVersion());
        $this->assertSame(array('Host' => 'example.com:80', 'Connection' => 'close'), $request->getHeaders());
    }

    public function testHeadersEventShouldReturnBinaryBodyBuffer()
    {
        $bodyBuffer = null;

        $parser = new RequestParser();
        $parser->on('request', function ($request) use (&$bodyBuffer) {
            $bodyBuffer = '';
            $request->on('data', function ($data) use (&$bodyBuffer) {
                $bodyBuffer = $data;
            });
        });

        $data = $this->createAdvancedPostRequest("\0x01\0x02\0x03\0x04\0x05");
        $parser->write($data);

        $this->assertSame("\0x01\0x02\0x03\0x04\0x05", $bodyBuffer);
    }

    public function testHeadersEventShouldParsePathAndQueryString()
    {
        $request = null;

        $parser = new RequestParser();
        $parser->on('request', function ($parsedRequest) use (&$request) {
            $request = $parsedRequest;
        });

        $data = $this->createAdvancedPostRequest();
        $parser->write($data);

        $this->assertInstanceOf('React\Http\Request', $request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/foo', $request->getPath());
        $this->assertSame(array('bar' => 'baz'), $request->getQuery());
        $this->assertSame('1.1', $request->getHttpVersion());
        $headers = array(
            'Host' => 'example.com:80',
            'User-Agent' => 'react/alpha',
            'Content-Length' => '0',
            'Connection' => 'close',
        );
        $this->assertSame($headers, $request->getHeaders());
    }

    public function testHeaderOverflowShouldEmitError()
    {
        $error = null;

        $parser = new RequestParser();
        $parser->on('request', $this->expectCallableNever());
        $parser->on('error', function ($e) use (&$error) {
            $error = $e;
        });

        $data = str_repeat('A', 4097);
        $parser->write($data);

        $this->assertInstanceOf('OverflowException', $error);
        $this->assertSame('Maximum header size of 4096 exceeded.', $error->getMessage());
    }

    private function createGetRequest()
    {
        $data = "GET / HTTP/1.1\r\n";
        $data .= "Host: example.com:80\r\n";
        $data .= "Connection: close\r\n";
        $data .= "\r\n";

        return $data;
    }

    private function createAdvancedPostRequest($body = '')
    {
        $contentLength = strlen($body);

        $data = "POST /foo?bar=baz HTTP/1.1\r\n";
        $data .= "Host: example.com:80\r\n";
        $data .= "User-Agent: react/alpha\r\n";
        $data .= "Content-Length: $contentLength\r\n";
        $data .= "Connection: close\r\n";
        $data .= "\r\n";
        $data .= $body;

        return $data;
    }
}
