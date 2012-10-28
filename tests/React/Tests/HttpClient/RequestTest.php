<?php

namespace React\Tests\HttpClient;

use Guzzle\Http\Message\Request as GuzzleRequest;
use React\HttpClient\Request;
use React\Stream\Stream;
use React\Tests\Socket\TestCase;

class RequestTest extends TestCase
{
    private $loop;
    private $connectionManager;
    private $stream;

    public function setUp()
    {
        $this->loop = $this->getMock('React\EventLoop\LoopInterface');
        $this->connectionManager = $this->getMock('React\HttpClient\ConnectionManagerInterface');

        $this->stream = $this->getMockBuilder('React\Stream\Stream')
            ->disableOriginalConstructor()
            ->getMock();

        $this->response = $this->getMockBuilder('React\HttpClient\Response')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /** @test */
    public function requestShouldBindToStreamEventsAndUseConnectionManager()
    {
        $guzzleRequest = new GuzzleRequest('GET', 'http://www.example.com');
        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);
        $stream = $this->stream;

        $this->connectionManager
            ->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80)
            ->will($this->returnCallback(function ($cb) use ($stream) {
                $cb($stream);
            }));

        $this->stream
            ->expects($this->at(0))
            ->method('on')
            ->with('drain', $this->identicalTo(array($request, 'handleDrain')));
        $this->stream
            ->expects($this->at(1))
            ->method('on')
            ->with('data', $this->identicalTo(array($request, 'handleData')));
        $this->stream
            ->expects($this->at(2))
            ->method('on')
            ->with('end', $this->identicalTo(array($request, 'handleEnd')));
        $this->stream
            ->expects($this->at(3))
            ->method('on')
            ->with('error', $this->identicalTo(array($request, 'handleError')));
        $this->stream
            ->expects($this->at(5))
            ->method('removeListener')
            ->with('drain', $this->identicalTo(array($request, 'handleDrain')));
        $this->stream
            ->expects($this->at(6))
            ->method('removeListener')
            ->with('data', $this->identicalTo(array($request, 'handleData')));
        $this->stream
            ->expects($this->at(7))
            ->method('removeListener')
            ->with('end', $this->identicalTo(array($request, 'handleEnd')));
        $this->stream
            ->expects($this->at(8))
            ->method('removeListener')
            ->with('error', $this->identicalTo(array($request, 'handleError')));

        $response = $this->response;

        $response->expects($this->once())
            ->method('emit')
            ->with('data', array('body'));

        $response->expects($this->at(0))
            ->method('on')
            ->with('end', $this->anything())
            ->will($this->returnCallback(function ($event, $cb) use (&$endCallback) {
                $endCallback = $cb;
            }));

        $factory = $this->createCallableMock();
        $factory->expects($this->once())
            ->method('__invoke')
            ->with('HTTP', '1.0', '200', 'OK', array('Content-Type' => 'text/plain'))
            ->will($this->returnValue($response));

        $request->setResponseFactory($factory);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with($response);

        $request->on('response', $handler);
        $request->on('close', $this->expectCallableNever());

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                null,
                $this->isInstanceof('React\HttpClient\Response'),
                $this->isInstanceof('React\HttpClient\Request')
            );

        $request->on('end', $handler);
        $request->end();

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");

        $this->assertNotNull($endCallback);
        call_user_func($endCallback);
    }

    /** @test */
    public function requestShouldEmitErrorIfConnectionFails()
    {
        $guzzleRequest = new GuzzleRequest('GET', 'http://www.example.com');
        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);

        $this->connectionManager
            ->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80)
            ->will($this->returnCallback(function ($cb) {
                $cb(null);
            }));

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('error', $handler);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                null,
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('end', $handler);
        $request->on('close', $this->expectCallableNever());

        $request->end();
    }

    /** @test */
    public function requestShouldEmitErrorIfConnectionEndsBeforeResponseIsParsed()
    {
        $guzzleRequest = new GuzzleRequest('GET', 'http://www.example.com');
        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);
        $stream = $this->stream;

        $this->connectionManager
            ->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80)
            ->will($this->returnCallback(function ($cb) use ($stream) {
                $cb($stream);
            }));

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('error', $handler);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                null,
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('end', $handler);
        $request->on('close', $this->expectCallableNever());

        $request->end();
        $request->handleEnd();
    }

    /** @test */
    public function requestShouldEmitErrorIfConnectionEmitsError()
    {
        $guzzleRequest = new GuzzleRequest('GET', 'http://www.example.com');
        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);
        $stream = $this->stream;

        $this->connectionManager
            ->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80)
            ->will($this->returnCallback(function ($cb) use ($stream) {
                $cb($stream);
            }));

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('Exception'),
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('error', $handler);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('Exception'),
                null,
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('end', $handler);
        $request->on('close', $this->expectCallableNever());

        $request->end();
        $request->handleError(new \Exception('test'));
    }

    /** @test */
    public function postRequestShouldSendAPostRequest()
    {
        $guzzleRequest = new GuzzleRequest('POST', 'http://www.example.com');
        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);
        $stream = $this->stream;

        $this->connectionManager
            ->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80)
            ->will($this->returnCallback(function ($cb) use ($stream) {
                $cb($stream);
            }));

        $this->stream
            ->expects($this->at(4))
            ->method('write')
            ->with($this->matchesRegularExpression("#^POST / HTTP/1\.0\r\nHost: www.example.com\r\nUser-Agent:.*\r\n\r\n$#"));
        $this->stream
            ->expects($this->at(5))
            ->method('write')
            ->with($this->identicalTo("some post data"));

        $factory = $this->createCallableMock();
        $factory->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($this->response));

        $request->setResponseFactory($factory);
        $request->end('some post data');

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");
    }

    /** @test */
    public function writeWithAPostRequestShouldSendToTheStream()
    {
        $guzzleRequest = new GuzzleRequest('POST', 'http://www.example.com');
        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);
        $stream = $this->stream;

        $this->connectionManager
            ->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80)
            ->will($this->returnCallback(function ($cb) use ($stream) {
                $cb($stream);
            }));

        $this->stream
            ->expects($this->at(4))
            ->method('write')
            ->with($this->matchesRegularExpression("#^POST / HTTP/1\.0\r\nHost: www.example.com\r\nUser-Agent:.*\r\n\r\n$#"));
        $this->stream
            ->expects($this->at(5))
            ->method('write')
            ->with($this->identicalTo("some"));
        $this->stream
            ->expects($this->at(6))
            ->method('write')
            ->with($this->identicalTo("post"));
        $this->stream
            ->expects($this->at(7))
            ->method('write')
            ->with($this->identicalTo("data"));

        $factory = $this->createCallableMock();
        $factory->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($this->response));

        $request->setResponseFactory($factory);

        $request->write("some");
        $request->write("post");
        $request->end("data");

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");
    }

    /** @test */
    public function pipeShouldPipeDataIntoTheRequestBody()
    {
        $guzzleRequest = new GuzzleRequest('POST', 'http://www.example.com');
        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);
        $stream = $this->stream;

        $this->connectionManager
            ->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80)
            ->will($this->returnCallback(function ($cb) use ($stream) {
                $cb($stream);
            }));

        $this->stream
            ->expects($this->at(4))
            ->method('write')
            ->with($this->matchesRegularExpression("#^POST / HTTP/1\.0\r\nHost: www.example.com\r\nUser-Agent:.*\r\n\r\n$#"));
        $this->stream
            ->expects($this->at(5))
            ->method('write')
            ->with($this->identicalTo("some"));
        $this->stream
            ->expects($this->at(6))
            ->method('write')
            ->with($this->identicalTo("post"));
        $this->stream
            ->expects($this->at(7))
            ->method('write')
            ->with($this->identicalTo("data"));

        $factory = $this->createCallableMock();
        $factory->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($this->response));

        $request->setResponseFactory($factory);

        $stream = fopen('php://memory', 'r+');
        $stream = new Stream($stream, $this->loop);

        $stream->pipe($request);
        $stream->emit('data', array('some'));
        $stream->emit('data', array('post'));
        $stream->emit('data', array('data'));

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage $data must be null or scalar
     */
    public function endShouldOnlyAcceptScalars()
    {
        $guzzleRequest = new GuzzleRequest('POST', 'http://www.example.com');
        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);

        $request->end(array());
    }

    /** @test */
    public function requestShouldRelayErrorEventsFromResponse()
    {
        $guzzleRequest = new GuzzleRequest('GET', 'http://www.example.com');
        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);
        $stream = $this->stream;

        $this->connectionManager
            ->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80)
            ->will($this->returnCallback(function ($cb) use ($stream) {
                $cb($stream);
            }));

        $response = $this->response;

        $response->expects($this->at(0))
            ->method('on')
            ->with('end', $this->anything());
        $response->expects($this->at(1))
            ->method('on')
            ->with('error', $this->anything())
            ->will($this->returnCallback(function ($event, $cb) use (&$errorCallback) {
                $errorCallback = $cb;
            }));

        $factory = $this->createCallableMock();
        $factory->expects($this->once())
            ->method('__invoke')
            ->with('HTTP', '1.0', '200', 'OK', array('Content-Type' => 'text/plain'))
            ->will($this->returnValue($response));

        $request->setResponseFactory($factory);
        $request->end();

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");

        $this->assertNotNull($errorCallback);
        call_user_func($errorCallback, new \Exception('test'));
    }
}

