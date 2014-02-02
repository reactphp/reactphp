<?php

namespace React\Tests\Dns\Query;

use React\Dns\Query\RetryExecutor;
use React\Dns\Query\Query;
use React\Dns\Model\Message;
use React\Dns\Query\TimeoutException;
use React\Dns\Model\Record;
use React\Promise;

class RetryExecutorTest extends \PHPUnit_Framework_TestCase
{
    /**
    * @covers React\Dns\Query\RetryExecutor
    * @test
    */
    public function queryShouldDelegateToDecoratedExecutor()
    {
        $executor = $this->createExecutorMock();
        $executor
            ->expects($this->once())
            ->method('query')
            ->with('8.8.8.8', $this->isInstanceOf('React\Dns\Query\Query'))
            ->will($this->returnValue($this->expectPromiseOnce()));

        $retryExecutor = new RetryExecutor($executor, 2);

        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);
        $retryExecutor->query('8.8.8.8', $query);
    }

    /**
    * @covers React\Dns\Query\RetryExecutor
    * @test
    */
    public function queryShouldRetryQueryOnTimeout()
    {
        $response = $this->createStandardResponse();

        $executor = $this->createExecutorMock();
        $executor
            ->expects($this->exactly(2))
            ->method('query')
            ->with('8.8.8.8', $this->isInstanceOf('React\Dns\Query\Query'))
            ->will($this->onConsecutiveCalls(
                $this->returnCallback(function ($domain, $query) {
                    return Promise\reject(new TimeoutException("timeout"));
                }),
                $this->returnCallback(function ($domain, $query) use ($response) {
                    return Promise\resolve($response);
                })
            ));

        $callback = $this->createCallableMock();
        $callback
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('React\Dns\Model\Message'));

        $errorback = $this->expectCallableNever();

        $retryExecutor = new RetryExecutor($executor, 2);

        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);
        $retryExecutor->query('8.8.8.8', $query)->then($callback, $errorback);
    }

    /**
    * @covers React\Dns\Query\RetryExecutor
    * @test
    */
    public function queryShouldStopRetryingAfterSomeAttempts()
    {
        $executor = $this->createExecutorMock();
        $executor
            ->expects($this->exactly(3))
            ->method('query')
            ->with('8.8.8.8', $this->isInstanceOf('React\Dns\Query\Query'))
            ->will($this->returnCallback(function ($domain, $query) {
                return Promise\reject(new TimeoutException("timeout"));
            }));

        $callback = $this->expectCallableNever();

        $errorback = $this->createCallableMock();
        $errorback
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('RuntimeException'));

        $retryExecutor = new RetryExecutor($executor, 2);

        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);
        $retryExecutor->query('8.8.8.8', $query)->then($callback, $errorback);
    }

    /**
    * @covers React\Dns\Query\RetryExecutor
    * @test
    */
    public function queryShouldForwardNonTimeoutErrors()
    {
        $executor = $this->createExecutorMock();
        $executor
            ->expects($this->once())
            ->method('query')
            ->with('8.8.8.8', $this->isInstanceOf('React\Dns\Query\Query'))
            ->will($this->returnCallback(function ($domain, $query) {
                return Promise\reject(new \Exception);
            }));

        $callback = $this->expectCallableNever();

        $errorback = $this->createCallableMock();
        $errorback
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Exception'));

        $retryExecutor = new RetryExecutor($executor, 2);

        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);
        $retryExecutor->query('8.8.8.8', $query)->then($callback, $errorback);
    }

    protected function expectCallableOnce()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke');

        return $mock;
    }

    protected function expectCallableNever()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->never())
            ->method('__invoke');

        return $mock;
    }

    protected function expectPromiseOnce($return = null)
    {
        $mock = $this->createPromiseMock();
        $mock
            ->expects($this->once())
            ->method('then')
            ->will($this->returnValue($return));

        return $mock;
    }

    protected function createCallableMock()
    {
        return $this->getMock('React\Tests\Socket\Stub\CallableStub');
    }

    protected function createExecutorMock()
    {
        return $this->getMock('React\Dns\Query\ExecutorInterface');
    }

    protected function createPromiseMock()
    {
        return $this->getMock('React\Promise\PromiseInterface');
    }

    protected function createStandardResponse()
    {
        $response = new Message();
        $response->header->set('qr', 1);
        $response->questions[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN);
        $response->answers[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131');
        $response->prepare();

        return $response;
    }
}

