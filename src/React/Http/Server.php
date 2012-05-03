<?php

namespace React\Http;

use Evenement\EventEmitter;
use React\Socket\ServerInterface as SocketServerInterface;
use React\Socket\Connection;

/**
 * Events:
 *  * request
 *  * upgrade
 */
class Server extends EventEmitter implements ServerInterface
{
    private $io;

    public function __construct(SocketServerInterface $io)
    {
        $this->io = $io;

        $server = $this;

        $this->io->on('connect', function ($conn) use ($server) {
            // TODO: http 1.1 keep-alive
            // TODO: chunked transfer encoding
            // TODO: multipart parsing
            // also for outgoing data (custom OutputStream?)

            $parser = new RequestHeaderParser();
            $parser->on('headers', function (Request $request, $bodyBuffer) use ($server, $conn, $parser) {
                $server->handleRequest($conn, $request, $bodyBuffer);

                $conn->removeListener('data', array($parser, 'feed'));
                $conn->on('data', function ($data) use ($request) {
                    $request->emit('data', array($data));
                });
            });

            $conn->on('data', array($parser, 'feed'));
        });
    }

    public function handleRequest(Connection $conn, Request $request, $bodyBuffer)
    {
        $response = new Response($conn);

        if (!$this->listeners('request')) {
            $response->end();
            return;
        }

        $this->emit('request', array($request, $response));
        $request->emit('data', array($bodyBuffer));
    }
}
