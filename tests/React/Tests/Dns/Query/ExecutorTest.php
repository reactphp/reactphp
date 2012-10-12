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
        $this->executor->query('8.8.8.8:53', $query, function () {});
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
        $this->executor->query('8.8.8.8:53', $query, function () {});
    }

    /** @test */
    public function resolveShouldRetryWithTcpIfResponseIsTruncated()
    {
        $conn = $this->createConnectionMock();

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
        $this->executor->query('8.8.8.8:53', $query, function () {});
    }

    /**
     * @test
     * @expectedException React\Dns\BadServerException
     * @expectedExceptionMessage The server set the truncated bit although we issued a TCP request
     **/
    public function resolveShouldFailIfResponseIsTruncatedAfterTcpRequest()
    {
        $conn = $this->createConnectionMock();

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

        $query = new Query(str_repeat('a', 512).'.igor.io', Message::TYPE_A, Message::CLASS_IN, 1345656451);
        $this->executor->query('8.8.8.8:53', $query, function () {});
    }

    private function returnStandardResponse()
    {
        $that = $this;

        $callback = function ($data, $response) use ($that) {
            $that->convertMessageToStandardResponse($response);
            return $response;
        };

        return $this->returnCallback($callback);
    }

    private function returnTruncatedResponse()
    {
        $that = $this;

        $callback = function ($data, $response) use ($that) {
            $that->convertMessageToTruncatedResponse($response);
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
}
