<?php

namespace React\Tests\Dns\Resolver;

use React\Dns\Resolver\Resolver;
use React\Dns\Query\Query;
use React\Dns\Model\Message;
use React\Dns\Model\Record;
use React\Promise;

class ResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function resolveShouldQueryARecords()
    {
        $executor = $this->createExecutorMock();
        $executor
            ->expects($this->once())
            ->method('query')
            ->with($this->anything(), $this->isInstanceOf('React\Dns\Query\Query'))
            ->will($this->returnCallback(function ($nameserver, $query) {
                $response = new Message();
                $response->header->set('qr', 1);
                $response->questions[] = new Record($query->name, $query->type, $query->class);
                $response->answers[] = new Record($query->name, $query->type, $query->class, 3600, '178.79.169.131');

                return Promise\resolve($response);
            }));

        $resolver = new Resolver('8.8.8.8:53', $executor);
        $resolver->resolve('igor.io')->then($this->expectCallableOnceWith('178.79.169.131'));
    }

    /** @test */
    public function resolveShouldFilterByName()
    {
        $executor = $this->createExecutorMock();
        $executor
            ->expects($this->once())
            ->method('query')
            ->with($this->anything(), $this->isInstanceOf('React\Dns\Query\Query'))
            ->will($this->returnCallback(function ($nameserver, $query) {
                $response = new Message();
                $response->header->set('qr', 1);
                $response->questions[] = new Record($query->name, $query->type, $query->class);
                $response->answers[] = new Record('foo.bar', $query->type, $query->class, 3600, '178.79.169.131');

                return Promise\resolve($response);
            }));

        $errback = $this->expectCallableOnceWith($this->isInstanceOf('React\Dns\RecordNotFoundException'));

        $resolver = new Resolver('8.8.8.8:53', $executor);
        $resolver->resolve('igor.io')->then($this->expectCallableNever(), $errback);
    }

    /** @test */
    public function resolveWithNoAnswersShouldThrowException()
    {
        $executor = $this->createExecutorMock();
        $executor
            ->expects($this->once())
            ->method('query')
            ->with($this->anything(), $this->isInstanceOf('React\Dns\Query\Query'))
            ->will($this->returnCallback(function ($nameserver, $query) {
                $response = new Message();
                $response->header->set('qr', 1);
                $response->questions[] = new Record($query->name, $query->type, $query->class);

                return Promise\resolve($response);
            }));

        $errback = $this->expectCallableOnceWith($this->isInstanceOf('React\Dns\RecordNotFoundException'));

        $resolver = new Resolver('8.8.8.8:53', $executor);
        $resolver->resolve('igor.io')->then($this->expectCallableNever(), $errback);
    }

    /**
     * @test
     */
    public function resolveWithNoAnswersShouldCallErrbackIfGiven()
    {
        $executor = $this->createExecutorMock();
        $executor
            ->expects($this->once())
            ->method('query')
            ->with($this->anything(), $this->isInstanceOf('React\Dns\Query\Query'))
            ->will($this->returnCallback(function ($nameserver, $query) {
                $response = new Message();
                $response->header->set('qr', 1);
                $response->questions[] = new Record($query->name, $query->type, $query->class);

                return Promise\resolve($response);
            }));

        $errback = $this->expectCallableOnceWith($this->isInstanceOf('React\Dns\RecordNotFoundException'));

        $resolver = new Resolver('8.8.8.8:53', $executor);
        $resolver->resolve('igor.io')->then($this->expectCallableNever(), $errback);
    }

    /** @test */
    public function resolveSouldProvideDefaultErrorbackToExecutor()
    {
        $executor = $this->createExecutorMock();
        $executor
            ->expects($this->once())
            ->method('query')
            ->with($this->anything(), $this->isInstanceOf('React\Dns\Query\Query'))
            ->will($this->returnCallback(function ($nameserver, $query) {
                return Promise\resolve();
            }));;

        $resolver = new Resolver('8.8.8.8:53', $executor);
        $resolver->resolve('igor.io')->then($this->expectCallableNever());
    }

    protected function expectCallableOnceWith($with)
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($with);

        return $mock;
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

    protected function createCallableMock()
    {
        return $this->getMock('React\Tests\Socket\Stub\CallableStub');
    }

    private function createExecutorMock()
    {
        return $this->getMock('React\Dns\Query\ExecutorInterface');
    }
}
