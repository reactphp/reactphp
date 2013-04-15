<?php

namespace React\Http;

use Evenement\EventEmitter;
use React\Socket\ServerInterface as SocketServerInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ReadableStreamInterface;

/** @event request */
class Server extends EventEmitter implements ServerInterface
{
    private $io;

    public function __construct(SocketServerInterface $io)
    {
        $this->io = $io;
        $this->io->on('connection', [$this, 'handleConnection']);
    }

    public function handleConnection(ConnectionInterface $conn)
    {
        $parser = new RequestParser();
        $parser->on('request', function ($request) use ($conn) {
            $this->handleRequest($conn, $request);
        });
        $parser->on('connection-end', [$conn, 'end']);

        $conn->pipe($parser);
    }

    public function handleRequest(ConnectionInterface $conn, Request $request)
    {
        $response = new Response($conn);
        $response->on('close', array($request, 'close'));

        if (!$this->listeners('request')) {
            $response->end();
            return;
        }

        $this->emit('request', array($request, $response));
    }
}
