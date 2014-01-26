<?php

namespace React\Tests\Dns\Query;

use React\Dns\Model\Message;
use React\Dns\Query\HostsFileExecutor;
use React\Dns\Query\Query;

class HostsFileExecutorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers React\Dns\Query\HostsFileExecutor
     */
    public function testQueryShouldUseFilesystem()
    {
        $triggerListener = null;
        $capturedResponse = null;
        $query = new Query('localhost', Message::TYPE_A, Message::CLASS_IN, time());

        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->once())
            ->method('addReadStream')
            ->will($this->returnCallback(function ($stream, $listener) use (&$triggerListener) {
                $triggerListener = function () use ($stream, $listener) {
                    call_user_func($listener, $stream);
                };
            }));

        $fallback = $this->getMock('React\Dns\Query\ExecutorInterface');

        $factory = new HostsFileExecutor($loop, $fallback, __DIR__.'/../Fixtures/etc/hosts');

        $factory->query('8.8.8.8', $query)->then(function ($response) use (&$capturedResponse) {
            $capturedResponse = $response;
        });

        $triggerListener();

        $this->assertNotNull($capturedResponse);
        $this->assertCount(1, $capturedResponse->answers);
        $this->assertSame('127.0.0.1', $capturedResponse->answers[0]->data);
    }

    /**
     * @covers React\Dns\Query\HostsFileExecutor
     */
    public function testQueryShouldFallbackIfFileCannotBeRead()
    {
        $triggerListener = null;
        $capturedResponse = null;
        $query = new Query('localhost', Message::TYPE_A, Message::CLASS_IN, time());
        $expectedResponse = new Message;

        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $fallback = $this->getMock('React\Dns\Query\ExecutorInterface');
        $fallback
            ->expects($this->once())
            ->method('query')
            ->with('8.8.8.8', $query)
            ->will($this->returnValue($expectedResponse));

        $factory = new HostsFileExecutor($loop, $fallback, __DIR__.'/../Fixtures/unexistant');

        $factory->query('8.8.8.8', $query)->then(function ($response) use (&$capturedResponse) {
            $capturedResponse = $response;
        });

        $this->assertSame($expectedResponse, $capturedResponse);
    }

    /**
     * @covers React\Dns\Query\HostsFileExecutor
     */
    public function testQueryShouldFallbackIfNameNotFoundInFile()
    {
        $triggerListener = null;
        $capturedResponse = null;
        $query = new Query('unexistant.example.com', Message::TYPE_A, Message::CLASS_IN, time());
        $expectedResponse = new Message;

        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $loop
            ->expects($this->once())
            ->method('addReadStream')
            ->will($this->returnCallback(function ($stream, $listener) use (&$triggerListener) {
                $triggerListener = function () use ($stream, $listener) {
                    call_user_func($listener, $stream);
                };
            }));

        $fallback = $this->getMock('React\Dns\Query\ExecutorInterface');
        $fallback
            ->expects($this->once())
            ->method('query')
            ->with('8.8.8.8', $query)
            ->will($this->returnValue($expectedResponse));

        $factory = new HostsFileExecutor($loop, $fallback, __DIR__.'/../Fixtures/etc/hosts');

        $factory->query('8.8.8.8', $query)->then(function ($response) use (&$capturedResponse) {
            $capturedResponse = $response;
        });

        $triggerListener();

        $this->assertSame($expectedResponse, $capturedResponse);
    }
}

