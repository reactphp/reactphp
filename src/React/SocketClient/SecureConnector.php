<?php

namespace React\SocketClient;

use React\EventLoop\LoopInterface;
use React\Stream\Stream;
use React\Promise\When;

class SecureConnector implements ConnectorInterface
{
    private $connector;
    private $loop;
    private $streamEncryption;

    public function __construct(ConnectorInterface $connector, LoopInterface $loop)
    {
        $this->connector = $connector;
        $this->loop = $loop;
        $this->streamEncryption = new StreamEncryption($loop);
    }

    public function createTcp($host, $port)
    {
        $streamEncryption = $this->streamEncryption;
        return $this->connector->createTcp($host, $port)->then(function (Stream $stream) use ($streamEncryption) {
            // (unencrypted) connection succeeded => try to enable encryption
            return $streamEncryption->enable($stream)->then(null, function ($error) use ($stream) {
                // establishing encryption failed => close invalid connection and return error
                $stream->close();
                throw $error;
            });
        });
    }
}
