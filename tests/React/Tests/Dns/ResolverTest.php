<?php

namespace React\Tests\Dns;

use React\Dns\Resolver;
use React\Dns\Query;
use React\Dns\Model\Message;
use React\Dns\Model\Record;

class ResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function resolveShouldQueryARecords()
    {
        $capturedQuery = null;

        $resolver = $this->createResolverMock(array('query'));
        $resolver
            ->expects($this->once())
            ->method('query')
            ->with($this->anything(), $this->isInstanceOf('React\Dns\Query'), $this->isInstanceOf('Closure'))
            ->will($this->returnCallback(function ($nameserver, $query, $callback) use (&$capturedQuery) {
                $capturedQuery = $query;
            }));

        $resolver->resolve('igor.io', function () {});

        $this->assertNotNull($capturedQuery);
        $this->assertSame('igor.io', $capturedQuery->name);
        $this->assertSame(Message::TYPE_A, $capturedQuery->type);
        $this->assertSame(Message::CLASS_IN, $capturedQuery->class);
    }

    /** @test */
    public function resolveShouldPickRandomResponse()
    {
        $resolver = $this->createResolverMock(array('query'));
        $resolver
            ->expects($this->once())
            ->method('query')
            ->will($this->returnCallback(function ($nameserver, $query, $callback) {
                $response = new Message();
                $response->header->set('qr', 1);
                $response->questions[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN);
                $response->answers[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131');
                $response->answers[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.132');
                $response->answers[] = new Record('igor.io', Message::TYPE_TXT, Message::CLASS_IN, 3600, 'foobar');
                $response->prepare();

                $callback($response);
            }));

        $capturedAddress = null;

        $resolver->resolve('igor.io', function ($address) use (&$capturedAddress) {
            $capturedAddress = $address;
        });

        $this->assertNotNull($capturedAddress);
        $this->assertTrue(in_array($capturedAddress, array('178.79.169.131', '178.79.169.132')));
    }

    /** @test */
    public function prepareRequestShouldCreateRequestWithRecursionDesired()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $resolver = new Resolver('8.8.8.8', $loop);

        $query = new Query('igor.io', Message::TYPE_A, Message::CLASS_IN);
        $request = $resolver->prepareRequest($query);

        $this->assertTrue($request->header->isQuery());
        $this->assertSame(1, $request->header->get('rd'));
    }

    /** @test */
    public function resolveShouldCreateUdpRequest()
    {
        $test = $this;

        $resolver = $this->createResolverMock(array('createConnection'));
        $resolver
            ->expects($this->once())
            ->method('createConnection')
            ->will($this->returnCallback(function ($nameserver, $transport) use ($test) {
                $test->assertSame('udp', $transport);

                $conn = $test->getMock('React\Socket\ConnectionInterface');
                return $conn;
            }));

        $resolver->resolve('igor.io', function () {});
    }

    /** @test */
    public function resolveShouldCreateTcpRequestIfRequestIsLargerThan512Bytes()
    {
        $test = $this;

        $resolver = $this->createResolverMock(array('createConnection'));
        $resolver
            ->expects($this->once())
            ->method('createConnection')
            ->will($this->returnCallback(function ($nameserver, $transport) use ($test) {
                $test->assertSame('tcp', $transport);

                $conn = $test->getMock('React\Socket\ConnectionInterface');
                return $conn;
            }));

        $resolver->resolve(str_repeat('a', 512).'.igor.io', function () {});
    }

    /** @test */
    public function resolveShouldRetryWithTcpIfResponseIsTruncated()
    {
        $this->markTestSkipped('PHPUnit issue #623.');

        $test = $this;

        $parser = $this->getMock('React\Dns\Protocol\Parser');
        $parser
            ->expects($this->at(0))
            ->method('parseChunk')
            ->with('the binary chunk', $this->isInstanceOf('React\Dns\Model\Message'))
            ->will($this->returnCallback(function ($data, $response) {
                $response->header->set('qr', 1);
                $response->header->set('tc', 1);
                $response->questions[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN);
                $response->answers[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131');
                $response->prepare();

                return $response;
            }));
        $parser
            ->expects($this->at(1))
            ->method('parseChunk')
            ->with('the binary chunk', $this->isInstanceOf('React\Dns\Model\Message'))
            ->will($this->returnCallback(function ($data, $response) use ($parser) {
                $response->header->set('qr', 1);
                $response->questions[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN);
                $response->answers[] = new Record('igor.io', Message::TYPE_A, Message::CLASS_IN, 3600, '178.79.169.131');
                $response->prepare();

                return $response;
            }));

        $resolver = $this->createResolverMock(array('createConnection'), $parser);

        $resolver
            ->expects($this->at(0))
            ->method('createConnection')
            ->will($this->returnCallback(array($this, 'validateUdpTransportAndCreateConnectionMock')));

        $resolver
            ->expects($this->at(1))
            ->method('createConnection')
            ->will($this->returnCallback(array($this, 'validateTcpTransportAndCreateConnectionMock')));

        $resolver->resolve('igor.io', function () {});
    }

    public function validateUdpTransportAndCreateConnectionMock($nameserver, $transport)
    {
        $this->assertSame('udp', $transport);

        return $this->createConnectionMock($this->once(), 'the binary chunk');
    }

    public function validateTcpTransportAndCreateConnectionMock($nameserver, $transport)
    {
        $this->assertSame('tcp', $transport);

        return $this->createConnectionMock($this->once(), 'the binary chunk');
    }

    public function createConnectionMock($expects, $data)
    {
        $conn = $this->getMock('React\Socket\ConnectionInterface');
        $conn
            ->expects($expects)
            ->method('on')
            ->with('data', $this->isInstanceOf('Closure'))
            ->will($this->returnCallback(function ($name, $callback) use ($data) {
                $callback($data);
            }));

        return $conn;
    }

    private function createResolverMock(array $methods, $parser = null, $dumper = null)
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $resolver = $this->getMockBuilder('React\Dns\Resolver')
            ->setMethods($methods)
            ->setConstructorArgs(array('8.8.8.8', $loop, $parser, $dumper))
            ->getMock();

        return $resolver;
    }
}
