<?php

namespace React\Tests\Dns\Query;

use React\Dns\Query\Executor;
use React\Dns\Query\Query;
use React\Dns\Model\Message;
use React\Dns\Model\Record;
use React\Dns\Protocol\BinaryDumper;

class ExecutorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->loop = $this->getMock('React\EventLoop\LoopInterface');
        $this->parser = $this->getMock('React\Dns\Protocol\Parser');
        $this->dumper = new BinaryDumper();

        $this->executor = new Executor($this->loop, $this->parser, $this->dumper);
    }

    /** @test */
    public function prepareRequestShouldCreateRequestWithRecursionDesired()
    {
        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);
        $request = $this->executor->prepareRequest($query);

        $this->assertTrue($request->header->isQuery());
        $this->assertSame(1, $request->header->get('rd'));
    }

    /** @test */
    public function queryShouldCreateUdpRequest()
    {
        $conn = $this->createConnectionMock();

        $this->executor = $this->createExecutorMock();
        $this->executor
            ->expects($this->once())
            ->method('createConnection')
            ->with('8.8.8.8:53', 'udp')
            ->will($this->returnNewConnectionMock());

        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);
        $this->executor->query('8.8.8.8:53', $query, function () {}, function () {});
    }

    /** @test */
    public function resolveShouldCreateTcpRequestIfRequestIsLargerThan512Bytes()
    {
        $conn = $this->createConnectionMock();

        $this->executor = $this->createExecutorMock();
        $this->executor
            ->expects($this->once())
            ->method('createConnection')
            ->with('8.8.8.8:53', 'tcp')
            ->will($this->returnNewConnectionMock());

        $query = new Query(str_repeat('a', 512).'.igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);
        $this->executor->query('8.8.8.8:53', $query, function () {}, function () {});
    }

    /** @test */
    public function resolveShouldRetryWithTcpIfResponseIsTruncated()
    {
        $conn = $this->createConnectionMock();

        $timer = $this->getMock('React\EventLoop\Timer\TimerInterface');

        $this->loop
            ->expects($this->any())
            ->method('addTimer')
            ->will($this->returnValue($timer));

        $this->parser
            ->expects($this->at(0))
            ->method('parseChunk')
            ->with($this->anything(), $this->isInstanceOf('React\Dns\Model\Message'))
            ->will($this->returnTruncatedResponse());
        $this->parser
            ->expects($this->at(1))
            ->method('parseChunk')
            ->with($this->anything(), $this->isInstanceOf('React\Dns\Model\Message'))
            ->will($this->returnStandardResponse());

        $this->executor = $this->createExecutorMock();
        $this->executor
            ->expects($this->at(0))
            ->method('createConnection')
            ->with('8.8.8.8:53', 'udp')
            ->will($this->returnNewConnectionMock());
        $this->executor
            ->expects($this->at(1))
            ->method('createConnection')
            ->with('8.8.8.8:53', 'tcp')
            ->will($this->returnNewConnectionMock());

        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);
        $this->executor->query('8.8.8.8:53', $query, function () {}, function () {});
    }

    /** @test */
    public function resolveShouldFailIfResponseIsTruncatedAfterTcpRequest()
    {
        $timer = $this->getMock('React\EventLoop\Timer\TimerInterface');

        $this->loop
            ->expects($this->any())
            ->method('addTimer')
            ->will($this->returnValue($timer));

        $this->parser
            ->expects($this->once())
            ->method('parseChunk')
            ->with($this->anything(), $this->isInstanceOf('React\Dns\Model\Message'))
            ->will($this->returnTruncatedResponse());

        $this->executor = $this->createExecutorMock();
        $this->executor
            ->expects($this->once())
            ->method('createConnection')
            ->with('8.8.8.8:53', 'tcp')
            ->will($this->returnNewConnectionMock());

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function($e) {
                return $e instanceof \React\Dns\BadServerException &&
                       'The server set the truncated bit although we issued a TCP request' === $e->getMessage();
            }));

        $query = new Query(str_repeat('a', 512).'.igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);
        $this->executor->query('8.8.8.8:53', $query)
            ->then($this->expectCallableNever(), $mock);
    }

    /** @test */
    public function resolveShouldCancelTimerWhenFullResponseIsReceived()
    {
        $conn = $this->createConnectionMock();

        $this->parser
            ->expects($this->once())
            ->method('parseChunk')
            ->with($this->anything(), $this->isInstanceOf('React\Dns\Model\Message'))
            ->will($this->returnStandardResponse());

        $this->executor = $this->createExecutorMock();
        $this->executor
            ->expects($this->at(0))
            ->method('createConnection')
            ->with('8.8.8.8:53', 'udp')
            ->will($this->returnNewConnectionMock());


        $timer = $this->getMock('React\EventLoop\Timer\TimerInterface');
        $timer
            ->expects($this->once())
            ->method('cancel');

        $this->loop
            ->expects($this->once())
            ->method('addTimer')
            ->with(5, $this->isInstanceOf('Closure'))
            ->will($this->returnValue($timer));

        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);
        $this->executor->query('8.8.8.8:53', $query, function () {}, function () {});
    }

    /** @test */
    public function resolveShouldCloseConnectionOnTimeout()
    {
        $this->executor = $this->createExecutorMock();
        $this->executor
            ->expects($this->at(0))
            ->method('createConnection')
            ->with('8.8.8.8:53', 'udp')
            ->will($this->returnNewConnectionMock());

        $this->loop
            ->expects($this->once())
            ->method('addTimer')
            ->with(5, $this->isInstanceOf('Closure'))
            ->will($this->returnCallback(function ($time, $callback) use (&$timerCallback) {
                $timerCallback = $callback;
            }));

        $this->loop
            ->expects($this->never())
            ->method('cancelTimer');

        $callback = $this->expectCallableNever();

        $errorback = $this->createCallableMock();
        $errorback
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->logicalAnd(
                $this->isInstanceOf('React\Dns\Query\TimeoutException'),
                $this->attribute($this->equalTo('DNS query for igor.io timed out'), 'message')
            ));

        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);
        $this->executor->query('8.8.8.8:53', $query)->then($callback, $errorback);

        $this->assertNotNull($timerCallback);
        $timerCallback();
    }

    private function returnStandardResponse()
    {
        $callback = function ($data, $response) {
            $this->convertMessageToStandardResponse($response);
            return $response;
        };

        return $this->returnCallback($callback);
    }

    private function returnTruncatedResponse()
    {
        $callback = function ($data, $response) {
            $this->convertMessageToTruncatedResponse($response);
            return $response;
        };

        return $this->returnCallback($callback);
    }

    public function convertMessageToStandardResponse(Message $response)
    {
        $response->header->set('qr', 1);
        $response->questions[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN);
        $response->answers[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131');
        $response->prepare();

        return $response;
    }

    public function convertMessageToTruncatedResponse(Message $response)
    {
        $this->convertMessageToStandardResponse($response);
        $response->header->set('tc', 1);
        $response->prepare();

        return $response;
    }

    private function returnNewConnectionMock()
    {
        $conn = $this->createConnectionMock();

        $callback = function () use ($conn) {
            return $conn;
        };

        return $this->returnCallback($callback);
    }

    private function createConnectionMock()
    {
        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $conn
            ->expects($this->any())
            ->method('on')
            ->with('data', $this->isInstanceOf('Closure'))
            ->will($this->returnCallback(function ($name, $callback) {
                $callback(null);
            }));

        return $conn;
    }

    private function createExecutorMock()
    {
        return $this->getMockBuilder('React\Dns\Query\Executor')
            ->setConstructorArgs(array($this->loop, $this->parser, $this->dumper))
            ->setMethods(array('createConnection'))
            ->getMock();
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
}
