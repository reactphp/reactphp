<?php

namespace React\Tests\Http\Client;

use Guzzle\Http\Message\Request as GuzzleRequest;
use React\Http\Client\Request;
use React\Tests\Socket\TestCase;

class RequestTest extends TestCase
{
    private $loop;
    private $connectionManager;
    private $stream;

    public function setUp()
    {
        $this->loop = $this->getMock('React\EventLoop\LoopInterface');
        $this->connectionManager = $this->getMock('React\Http\Client\ConnectionManagerInterface');
        $this->stream = $this->getMock('React\Stream\Stream', array(), array(), '', false);
        $this->response = $this->getMock('React\Http\Client\Response', array(), array(), '', false);
    }

    public function testRequest()
    {
        $that = $this;

        $guzzleRequest = new GuzzleRequest('GET', 'http://www.example.com');

        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);

        $stream = $this->stream;

        $this->connectionManager->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80, false)
            ->will($this->returnCallback(function($cb) use ($stream) {
                $cb($stream);
            }))
            ;

        $this->stream->expects($this->at(0))
            ->method('on')
            ->with('data', $this->identicalTo(array($request, 'handleData')))
            ;
        $this->stream->expects($this->at(1))
            ->method('on')
            ->with('end', $this->identicalTo(array($request, 'handleEnd')))
            ;
        $this->stream->expects($this->at(2))
            ->method('on')
            ->with('error', $this->identicalTo(array($request, 'handleError')))
            ;

        $this->stream->expects($this->at(3))
            ->method('write')
            ->with($this->matchesRegularExpression("#^GET / HTTP/1\.0\r\nHost: www.example.com\r\n.*\r\n\r\n$#"))
            ;

        $this->stream->expects($this->at(4))
            ->method('removeListener')
            ->with('data', $this->identicalTo(array($request, 'handleData')))
            ;
        $this->stream->expects($this->at(5))
            ->method('removeListener')
            ->with('end', $this->identicalTo(array($request, 'handleEnd')))
            ;
        $this->stream->expects($this->at(6))
            ->method('removeListener')
            ->with('error', $this->identicalTo(array($request, 'handleError')))
            ;

        $response = $this->response;

        $response->expects($this->once())
            ->method('emit')
            ->with('data', array('body'))
            ;

        $response->expects($this->at(0))
            ->method('on')
            ->with('end', $this->anything())
            ->will($this->returnCallback(function($event, $cb) use (&$endCallback) {
                $endCallback = $cb;
            }));

        $factory = $this->createCallableMock();
        $factory->expects($this->once())
            ->method('__invoke')
            ->with('HTTP', '1.0', '200', 'OK', array('Content-Type' => 'text/plain'))
            ->will($this->returnValue($response))
            ;
        $request->setResponseFactory($factory);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with($response)
            ; 
        $request->on('response', $handler);

        $request->on('close', $this->expectCallableNever());

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                null,
                $this->isInstanceof('React\Http\Client\Response'),
                $this->isInstanceof('React\Http\Client\Request')
            )
            ; 

        $request->on('end', $handler);

        $request->end();

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");

        $this->assertNotNull($endCallback);
        call_user_func($endCallback);
    }

    public function testRequestEmitsErrorIfConnectionFails()
    {
        $that = $this;

        $guzzleRequest = new GuzzleRequest('GET', 'http://www.example.com');

        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);

        $this->connectionManager->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80, false)
            ->will($this->returnCallback(function($cb) {
                $cb(null);
            }))
            ;

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                $this->isInstanceOf('React\Http\Client\Request')
            )
            ;

        $request->on('error', $handler);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                null,
                $this->isInstanceOf('React\Http\Client\Request')
            )
            ;

        $request->on('end', $handler);

        $request->on('close', $this->expectCallableNever());

        $request->end();
    }

    public function testRequestEmitsErrorIfConnectionEndsBeforeResponseIsParsed()
    {
        $that = $this;

        $guzzleRequest = new GuzzleRequest('GET', 'http://www.example.com');

        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);

        $stream = $this->stream;

        $this->connectionManager->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80, false)
            ->will($this->returnCallback(function($cb) use ($stream) {
                $cb($stream);
            }))
            ;

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                $this->isInstanceOf('React\Http\Client\Request')
            );

        $request->on('error', $handler);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                null,
                $this->isInstanceOf('React\Http\Client\Request')
            )
            ;

        $request->on('end', $handler);

        $request->on('close', $this->expectCallableNever());

        $request->end();
        $request->handleEnd();
    }

    public function testRequestEmitsErrorIfConnectionEmitsError()
    {
        $that = $this;

        $guzzleRequest = new GuzzleRequest('GET', 'http://www.example.com');

        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);

        $stream = $this->stream;

        $this->connectionManager->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80, false)
            ->will($this->returnCallback(function($cb) use ($stream) {
                $cb($stream);
            }))
            ;

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('Exception'),
                $this->isInstanceOf('React\Http\Client\Request')
            )
            ;

        $request->on('error', $handler);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('Exception'),
                null,
                $this->isInstanceOf('React\Http\Client\Request')
            )
            ;

        $request->on('end', $handler);

        $request->on('close', $this->expectCallableNever());

        $request->end();
        $request->handleError(new \Exception('test'));
    }

    public function testPostRequest()
    {
        $that = $this;

        $guzzleRequest = new GuzzleRequest('POST', 'http://www.example.com');

        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);

        $stream = $this->stream;

        $this->connectionManager->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80, false)
            ->will($this->returnCallback(function($cb) use ($stream) {
                $cb($stream);
            }))
            ;

        $this->stream->expects($this->at(3))
            ->method('write')
            ->with($this->matchesRegularExpression("#^POST / HTTP/1\.0\r\nHost: www.example.com\r\nUser-Agent:.*\r\n\r\nsome post data$#"))
            ;

        $factory = $this->createCallableMock();
        $factory->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($this->response))
            ;
        $request->setResponseFactory($factory);

        $request->end('some post data');

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage $data must be null or scalar
     */
    public function testEndAcceptsOnlyScalars()
    {
        $that = $this;

        $guzzleRequest = new GuzzleRequest('POST', 'http://www.example.com');

        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);

        $request->end(array());
    }

    public function testRequestRelaysErrorEventsFromResponse()
    {
        $that = $this;

        $guzzleRequest = new GuzzleRequest('GET', 'http://www.example.com');

        $request = new Request($this->loop, $this->connectionManager, $guzzleRequest);

        $stream = $this->stream;

        $this->connectionManager->expects($this->once())
            ->method('getConnection')
            ->with($this->anything(), 'www.example.com', 80, false)
            ->will($this->returnCallback(function($cb) use ($stream) {
                $cb($stream);
            }))
            ;

        $response = $this->response;

        $response->expects($this->at(0))
            ->method('on')
            ->with('end', $this->anything());

        $response->expects($this->at(1))
            ->method('on')
            ->with('error', $this->anything())
            ->will($this->returnCallback(function($event, $cb) use (&$errorCallback) {
                $errorCallback = $cb;
            }));

        $factory = $this->createCallableMock();
        $factory->expects($this->once())
            ->method('__invoke')
            ->with('HTTP', '1.0', '200', 'OK', array('Content-Type' => 'text/plain'))
            ->will($this->returnValue($response))
            ;
        $request->setResponseFactory($factory);

        $request->end();

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");

        $this->assertNotNull($errorCallback);
        call_user_func($errorCallback, new \Exception('test'));
    }
}

