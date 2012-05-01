<?php

namespace React\Http;

use Evenement\EventEmitter;
use React\Socket\ServerInterface as SocketServerInterface;
use React\Socket\Connection;
use React\Http\OutputStream\SocketOutputStream;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Server extends EventEmitter implements ServerInterface
{
    private $io;
    private $kernel;

    public function __construct(SocketServerInterface $io, HttpKernelInterface $kernel)
    {
        $this->io = $io;
        $this->kernel = $kernel;

        $server = $this;

        $this->io->on('connect', function ($conn) use ($server) {
            // TODO: http 1.1 keep-alive
            // TODO: chunked transfer encoding
            // TODO: multipart parsing
            // also for outgoing data (custom OutputStream?)

            $parser = new RequestHeaderParser();
            $parser->on('headers', function (StreamedRequest $request, $bodyBuffer) use ($server, $conn) {
                $server->handleRequest($conn, $request, $bodyBuffer);
            });

            $parser->on('headers', function () use ($conn, $parser) {
                $conn->removeListener('data', array($parser, 'feed'));
            });

            $conn->on('data', array($parser, 'feed'));
        });
    }

    public function handleRequest(Connection $conn, StreamedRequest $request, $bodyBuffer)
    {
        $response = $this->kernel->handle($request);

        // write headers
        // if response is set, also write response
        $conn->write((string) $response);

        if ($response instanceof StreamedResponse) {
            $outputStream = new SocketOutputStream($conn);
            $response->setOutputStream($outputStream);
            $response->sendContent();

            $request->emitData($bodyBuffer);
            $conn->on('data', array($request, 'emitData'));
        } else {
            // TODO: do not close for keep-alive
            $conn->close();
        }
    }
}
