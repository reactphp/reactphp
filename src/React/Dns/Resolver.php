<?php

namespace React\Dns;

use React\Dns\Model\Message;
use React\Socket\Connection;

class Resolver
{
    public function resolve($domain, $callback)
    {
        $nameserver = '8.8.8.8';
        $query = new Query($domain, 'A', 'IN');

        $this->query($nameserver, $query, function (Message $response) use ($callback) {
            $answer = $response->answers[array_rand($response->answers)]->data;
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
        $request->question = (array) $query;

        $response = new Message();

        $conn = new Connection(fopen("udp://$nameserver:53"), $this->loop);
        $conn->on('data', function ($data) use ($conn, $response, $callback) {
            if ($parser->parseChunk($data, $response)) {
                $conn->end();
                $callback($response);
            }
        });
        $conn->write($dumper->toBinary($request));
    }
}
