<?php

namespace React\Dns;

use React\Socket\Connection;

class Resolver
{
    public function resolve($domain, $callback)
    {
        $nameserver = '8.8.8.8';
        $query = new Query($domain, 'A', 'IN');

        $this->query($nameserver, $query, function (Message $response) use ($callback) {
            $answer = $response->answer[array_rand($response->answer)]->data->address;
            $callback($address);
        });
    }

    public function query($nameserver, Query $query, $callback)
    {
        $dumper = new BinaryDumper();
        $parser = new Parser();

        $message = new Message();
        $message->header = array(
            $query...
        );
        $message->question = array(
            $query...
        );

        $response = new Message();

        $conn = new Connection(fopen("udp://$nameserver:53"), $this->loop);
        $conn->on('data', function ($data) use ($conn, $response, $callback) {
            if ($parser->parseChunk($data, $response)) {
                $conn->end();
                $callback($response);
            }
        });
        $conn->write($dumper->toBinary($message));
    }
}
