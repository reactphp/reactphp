<?php

namespace React\Dns\Query;

use React\Dns\BadServerException;
use React\Dns\Model\Message;
use React\Dns\Protocol\Parser;
use React\Dns\Protocol\BinaryDumper;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Socket\AddressFactory;
use React\Socket\RemoteAddressInterface;
use React\Socket\TcpAddress;
use React\Socket\UdpAddress;
use React\Socket\Connection;

class Executor implements ExecutorInterface
{
    private $loop;
    private $parser;
    private $dumper;
    private $timeout;

    public function __construct(LoopInterface $loop, Parser $parser, BinaryDumper $dumper, $timeout = 5)
    {
        $this->loop = $loop;
        $this->parser = $parser;
        $this->dumper = $dumper;
        $this->timeout = $timeout;
    }

    public function query($nameserver, Query $query)
    {
        $request = $this->prepareRequest($query);
        $queryData = $this->dumper->toBinary($request);

        if (false === ($nameserver instanceof RemoteAddressInterface)) {
            $nameserver = AddressFactory::create($nameserver);
        }

        if (strlen($queryData) > 512 && $nameserver instanceof UdpAddress) {
            $nameserver = $nameserver->toTcpAddress();
        }

        else if ($nameserver instanceof TcpAddress) {
            $nameserver = $nameserver->toUdpAddress();
        }

        return $this->doQuery($nameserver, $queryData, $query->name);
    }

    public function prepareRequest(Query $query)
    {
        $request = new Message();
        $request->header->set('id', $this->generateId());
        $request->header->set('rd', 1);
        $request->questions[] = (array) $query;
        $request->prepare();

        return $request;
    }

    public function doQuery(RemoteAddressInterface $nameserver, $queryData, $name)
    {
        $parser = $this->parser;
        $loop = $this->loop;

        $response = new Message();
        $deferred = new Deferred();

        $retryWithTcp = function () use ($nameserver, $queryData, $name) {
            return $this->doQuery($nameserver->toTcpAddress(), $queryData, $name);
        };

        $timer = $this->loop->addTimer($this->timeout, function () use (&$conn, $name, $deferred) {
            $conn->close();
            $deferred->reject(new TimeoutException(sprintf("DNS query for %s timed out", $name)));
        });

        $conn = $this->createConnection($nameserver);
        $conn->on('data', function ($data) use ($retryWithTcp, $conn, $parser, $response, $nameserver, $deferred, $timer) {
            $responseReady = $parser->parseChunk($data, $response);

            if (!$responseReady) {
                return;
            }

            $timer->cancel();

            if ($response->header->isTruncated()) {
                if ($nameserver instanceof TcpAddress) {
                    $deferred->reject(new BadServerException('The server set the truncated bit although we issued a TCP request'));
                } else {
                    $conn->end();
                    $deferred->resolve($retryWithTcp());
                }

                return;
            }

            $conn->end();
            $deferred->resolve($response);
        });
        $conn->write($queryData);

        return $deferred->promise();
    }

    protected function generateId()
    {
        return mt_rand(0, 0xffff);
    }

    protected function createConnection(RemoteAddressInterface $nameserver)
    {
        if ($nameserver->getPort() === null) {
            $nameserver->setPort(53);
        }

        $fd = stream_socket_client($nameserver);
        $conn = new Connection($fd, $this->loop);

        return $conn;
    }
}
