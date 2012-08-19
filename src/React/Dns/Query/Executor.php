<?php

namespace React\Dns\Query;

use React\Dns\BadServerException;
use React\Dns\RecordNotFoundException;
use React\Dns\Model\Message;
use React\Dns\Protocol\Parser;
use React\Dns\Protocol\BinaryDumper;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;

class Executor implements ExecutorInterface
{
    private $loop;
    private $parser;
    private $dumper;

    public function __construct(LoopInterface $loop, Parser $parser, BinaryDumper $dumper)
    {
        $this->loop = $loop;
        $this->parser = $parser;
        $this->dumper = $dumper;
    }

    public function query($nameserver, Query $query, $callback)
    {
        $request = $this->prepareRequest($query);

        $queryData = $this->dumper->toBinary($request);
        $transport = strlen($queryData) > 512 ? 'tcp' : 'udp';

        $this->doQuery($nameserver, $transport, $queryData, $callback);
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

    public function doQuery($nameserver, $transport, $queryData, $callback)
    {
        $that = $this;
        $parser = $this->parser;

        $response = new Message();

        $retryWithTcp = function () use ($that, $nameserver, $queryData, $callback) {
            $that->doQuery($nameserver, 'tcp', $queryData, $callback);
        };

        $conn = $this->createConnection($nameserver, $transport);
        $conn->on('data', function ($data) use ($that, $retryWithTcp, $conn, $parser, $response, $transport, $callback) {
            $responseReady = $parser->parseChunk($data, $response);

            if (!$responseReady) {
                return;
            }

            if ($response->header->isTruncated()) {
                if ('tcp' === $transport) {
                    throw new BadServerException('The server set the truncated bit although we issued a TCP request');
                }

                $conn->end();
                $retryWithTcp();
                return;
            }

            $conn->end();
            $callback($response);
        });
        $conn->write($queryData);
    }

    protected function generateId()
    {
        return mt_rand(0, 0xffff);
    }

    protected function createConnection($nameserver, $transport)
    {
        $fd = stream_socket_client("$transport://$nameserver");
        $conn = new Connection($fd, $this->loop);

        return $conn;
    }
}
