<?php

namespace React\Http\Client;

use React\EventLoop\LoopInterface;
use React\Stream\Stream;

class SecureConnectionManager extends ConnectionManager
{
    public function handleConnectedSocket($callback, $socket)
    {
        $loop = $this->loop;

        $enableCrypto = function() use ($callback, $socket, $loop) {

            $result = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            // crypto was successfully enabled
            if (true === $result) {
                $loop->removeWriteStream($socket);
                $loop->removeReadStream($socket);
                call_user_func($callback, new Stream($socket, $loop));

            // an error occured
            } else if (false === $result) {
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


