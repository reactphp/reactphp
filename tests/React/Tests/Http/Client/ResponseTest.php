<?php

namespace React\Tests\Http\Client;

use React\Http\Client\Response;
use React\Tests\Socket\TestCase;

class ResponseTest extends TestCase
{
    private $loop;
    private $stream;

    public function setUp()
    {
        $this->loop = $this->getMock('React\EventLoop\LoopInterface');
        $this->stream = $this->getMock('React\Stream\Stream', array(), array(), '', false);
    }

    public function testResponse()
    {
        $this->stream->expects($this->at(0))
            ->method('on')
            ->with('data', $this->anything())
            ;
        $this->stream->expects($this->at(1))
            ->method('on')
            ->with('error', $this->anything())
            ;
        $this->stream->expects($this->at(2))
            ->method('on')
            ->with('end', $this->anything())
            ;

        $response = new Response($this->loop, $this->stream, 'HTTP', '1.0', '200', 'OK', array('Content-Type' => 'text/plain'));

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with('some data', $this->anything())
            ;

        $response->on('data', $handler);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('React\Http\Client\Response'))
            ;

        $response->on('end', $handler);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('React\Http\Client\Response'))
            ;

        $response->on('close', $handler);

        $this->stream->expects($this->at(0))
            ->method('end')
            ;

        $response->handleData('some data');

        $response->handleEnd();
    }

    public function testClosedResponseCannotBeResumedOrPaused()
    {
        $response = new response($this->loop, $this->stream, 'http', '1.0', '200', 'ok', array('content-type' => 'text/plain'));

        $this->stream->expects($this->never())
            ->method('pause');

        $this->stream->expects($this->never())
            ->method('resume');

        $response->handleEnd();

        $response->resume();
        $response->pause();
    }
}

