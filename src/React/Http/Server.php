<?php

namespace React\Http;

use Evenement\EventEmitter;
use React\Socket\ServerInterface as SocketServerInterface;
use React\Socket\ConnectionInterface;

/** @event request */
class Server extends EventEmitter implements ServerInterface
{
    public function __construct(SocketServerInterface $io)
    {
        $server = $this;

        $io->on('connection', function ($conn) use ($server) {
            // TODO: chunked transfer encoding (also for outgoing data)
            // TODO: multipart parsing
            $server->awaitRequest($conn);
        });
    }
    
    public function awaitRequest(ConnectionInterface $conn)
    {
        $server = $this;
        
        $parser = new RequestHeaderParser($conn);
        
        $parser->on('headers', function (Request $request, $bodyBuffer) use ($server, $conn) {
            $server->handleRequest($conn, $request, $bodyBuffer);
        });
    }

    public function handleRequest(ConnectionInterface $conn, Request $request, $bodyBuffer)
    {
        $response = new Response($conn);
        
        $server = $this;
        $response->on('end', function () use ($request, $server, $conn) {
            $request->close();
            $server->awaitRequest($conn);
        });

        if (!$this->listeners('request')) {
            $response->end();

            return;
        }

        $this->emit('request', array($request, $response));
        $request->emit('data', array($bodyBuffer));
    }
}
