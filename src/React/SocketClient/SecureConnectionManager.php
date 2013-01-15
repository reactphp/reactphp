<?php

namespace React\SocketClient;

use React\EventLoop\LoopInterface;
use React\Stream\Stream;

class SecureConnectionManager implements ConnectionManagerInterface
{
    protected $connectionManager;
    protected $loop;
    protected $streamEncryption;

    public function __construct(ConnectionManagerInterface $connectionManager, LoopInterface $loop)
    {
        $this->connectionManager = $connectionManager;
        $this->loop = $loop;
        $this->streamEncryption = new StreamEncryption($loop);
    }

    public function getConnection($host, $port)
    {
        $streamEncryption = $this->streamEncryption;
        return $this->connectionManager->getConnection($host, $port)->then(function (Stream $stream) use ($streamEncryption) {
            // (unencrypted) connection succeeded => try to enable encryption
            return $streamEncryption->enable($stream)->then(null, function ($error) use ($stream) {
                // establishing encryption failed => close invalid connection and return error
                $stream->close();
                throw $error;
            });
        });
    }
}
