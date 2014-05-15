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
        $io->on('connection', array($this, 'awaitRequest'));
    }
    
    public function awaitRequest(ConnectionInterface $conn)
    {
        // TODO: chunked transfer encoding (also for outgoing data)
        // TODO: multipart parsing
        
        $server = $this;
        
        $parser = new RequestHeaderParser($conn);
        
        $parser->on('headers', function (Request $request, $bodyBuffer) use ($server, $conn) {
            $server->handleRequest($conn, $request, $bodyBuffer);
        });
    }

    public function handleRequest(ConnectionInterface $conn, Request $request, $bodyBuffer)
    {
        $requestHttpVersion = $request->getHttpVersion();
        
        if ('1.0' === $requestHttpVersion) {
            $keepAlive = (0 === strcasecmp('keep-alive', $request->getHeader('connection')));
        } else {
            $keepAlive = (0 !== strcasecmp('close', $request->getHeader('connection')));
        }
        
        $response = new Response($conn, $keepAlive, $requestHttpVersion);
        
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
