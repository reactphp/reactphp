<?php

namespace React\Dns;

use React\Dns\Model\Message;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;

class Resolver
{
    private $nameserver;
    private $loop;

    public function __construct($nameserver, LoopInterface $loop)
    {
        $this->nameserver = $this->addPortToServerIfMissing($nameserver);
        $this->loop = $loop;
    }

    public function resolve($domain, $callback)
    {
        $query = new Query($domain, Message::TYPE_A, Message::CLASS_IN);

        $this->query($this->nameserver, $query, function (Message $response) use ($callback) {
            $answer = $response->answers[array_rand($response->answers)];
            $address = $answer->data;
            $callback($address);
        });
    }

    public function query($nameserver, Query $query, $callback)
    {
        $dumper = new BinaryDumper();
        $parser = new Parser();

        $request = new Message();
        $request->headers->set('id', rand());
        $request->headers->set('rd', 1);
        $request->questions[] = (array) $query;
        $request->prepare();

        $queryData = $dumper->toBinary($request);
        $transport = strlen($queryData) > 512 ? 'tcp' : 'udp';

        $this->doQuery($nameserver, $transport, $queryData, $parser, $callback);
    }

    public function doQuery($nameserver, $transport, $queryData, Parser $parser, $callback)
    {
        $that = $this;

        $response = new Message();

        $fd = stream_socket_client("$transport://$nameserver");
        $conn = new Connection($fd, $this->loop);
        $conn->on('data', function ($data) use ($that, $conn, $parser, $response, $callback) {
            $responseReady = $parser->parseChunk($data, $response);
            if ($responseReady) {
                if ($response->headers->isTruncated()) {
                    $conn->end();
                    $that->doQuery($nameserver, 'tcp', $queryData, $parser, $callback);
                    return;
                }
                $conn->end();
                $callback($response);
            }
        });
        $conn->write($queryData);
    }

    private function addPortToServerIfMissing($nameserver)
    {
        return false === strpos($nameserver, ':') ? "$nameserver:53" : $nameserver;
    }
}
