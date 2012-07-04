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
        $this->nameserver = $nameserver;
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

        $response = new Message();

        $fd = stream_socket_client("udp://$nameserver:53");
        $conn = new Connection($fd, $this->loop);
        $conn->on('data', function ($data) use ($conn, $parser, $response, $callback) {
            if ($parser->parseChunk($data, $response)) {
                $conn->end();
                $callback($response);
            }
        });
        $conn->write($dumper->toBinary($request));
    }
}
