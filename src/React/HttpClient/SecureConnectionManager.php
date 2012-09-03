<?php

namespace React\HttpClient;

use React\EventLoop\LoopInterface;
use React\Stream\Stream;

class SecureConnectionManager extends ConnectionManager
{
    public function handleConnectedSocket($callback, $socket)
    {
        $loop = $this->loop;

        $enableCrypto = function () use ($callback, $socket, $loop) {

            $result = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            if (true === $result) {
                // crypto was successfully enabled
                $loop->removeWriteStream($socket);
                $loop->removeReadStream($socket);
                call_user_func($callback, new Stream($socket, $loop));

            } else if (false === $result) {
                // an error occured
                $loop->removeWriteStream($socket);
                $loop->removeReadStream($socket);
                call_user_func($callback, null);

            } else {
                // need more data, will retry
            }
        };

        $this->loop->addWriteStream($socket, $enableCrypto);
        $this->loop->addReadStream($socket, $enableCrypto);

        $enableCrypto();
    }
}


