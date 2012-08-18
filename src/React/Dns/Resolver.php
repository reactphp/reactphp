<?php

namespace React\Dns;

use React\Dns\Model\Message;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;

class Resolver
{
    private $nameserver;
    private $loop;
    private $parser;
    private $dumper;

    public function __construct($nameserver, LoopInterface $loop, Parser $parser = null, BinaryDumper $dumper = null)
    {
        $this->nameserver = $this->addPortToServerIfMissing($nameserver);
        $this->loop = $loop;
        $this->parser = $parser ?: new Parser();
        $this->dumper = $dumper ?: new BinaryDumper();
    }

    public function resolve($domain, $callback, $errback = null)
    {
        $that = $this;

        $query = new Query($domain, Message::TYPE_A, Message::CLASS_IN);

        $this->query($this->nameserver, $query, function (Message $response) use ($that, $callback, $errback) {
            try {
                $answer = $that->pickRandomAnswerOfType($response, Message::TYPE_A);
                $address = $answer->data;
                $callback($address);
            } catch (RecordNotFoundException $e) {
                if (!$errback) {
                    throw $e;
                }

                $errback($e);
            }
        });
    }

    public function query($nameserver, Query $query, $callback)
    {
        $request = $this->prepareRequest($query);

        $queryData = $this->dumper->toBinary($request);
        $transport = strlen($queryData) > 512 ? 'tcp' : 'udp';

        $this->doQuery($nameserver, $transport, $queryData, $callback);
    }

    public function pickRandomAnswerOfType(Message $response, $type)
    {
        $filteredAnswers = array_filter($response->answers, function ($answer) use ($type) {
            return $type === $answer->type;
        });

        if (0 === count($filteredAnswers)) {
            $message = sprintf('DNS Request did not return valid answer. Received answers: %s', json_encode($response->answers));
            throw new RecordNotFoundException($message);
        }

        $answer = $filteredAnswers[array_rand($filteredAnswers)];

        return $answer;
    }

    public function prepareRequest(Query $query)
    {
        $request = new Message();
        $request->header->set('id', rand());
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

    protected function createConnection($nameserver, $transport)
    {
        $fd = stream_socket_client("$transport://$nameserver");
        $conn = new Connection($fd, $this->loop);

        return $conn;
    }

    protected function addPortToServerIfMissing($nameserver)
    {
        return false === strpos($nameserver, ':') ? "$nameserver:53" : $nameserver;
    }
}
