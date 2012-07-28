<?php

namespace React\Tests\Http\Client;

use Guzzle\Http\Message\Request as GuzzleRequest;
use React\Http\Client\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
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

        $factory = $this->getMock('React\Tests\Http\Client\InvokableInterface');
        $factory->expects($this->once())
            ->method('__invoke')
            ->with('HTTP', '1.0', '200', 'OK', array('Content-Type' => 'text/plain'))
            ->will($this->returnValue($response))
            ;
        $request->setResponseFactory($factory);

        $handler = $this->getMock('React\Tests\Http\Client\InvokableInterface');
        $handler->expects($this->once())
            ->method('__invoke')
            ->with($response)
            ; 
        $request->on('response', $handler);

        $request->send();

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");

        $this->assertNotNull($response);
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

        $handler = $this->getMock('React\Tests\Http\Client\InvokableInterface');
        $handler->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ;

        $request->on('error', $handler);

        $request->send();
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

        $handler = $this->getMock('React\Tests\Http\Client\InvokableInterface');
        $handler->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ;

        $request->on('error', $handler);

        $request->send();
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

        $handler = $this->getMock('React\Tests\Http\Client\InvokableInterface');
        $handler->expects($this->once())
            ->method('__invoke')
            ->with($request)
            ;

        $request->on('error', $handler);

        $request->send();
        $request->handleError();
    }
}

