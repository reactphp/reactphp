<?php

namespace React\Http\Client;

use React\EventLoop\LoopInterface;
use React\Stream\Stream;

class ConnectionManager implements ConnectionManagerInterface
{
    private $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function getConnection($callback, $host, $port, $https = false)
    {
        $url = $this->getSocketUrl($host, $port);

        $socket = stream_socket_client($url, $errno, $errstr, ini_get("default_socket_timeout"), STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

        if (!$socket) {
            call_user_func($callback, null);
            return;
        }

        stream_set_blocking($socket, 0);

        // wait for connection

        $loop = $this->loop;
        $that = $this;

        $this->loop->addWriteStream($socket, function() use ($that, $callback, $socket, $loop, $https) {

            $loop->removeWriteStream($socket);

            $that->handleConnectedSocket($callback, $socket, $https);
        });
    }

    public function handleConnectedSocket($callback, $socket, $https)
    {
        if (!$https) {
            call_user_func($callback, new Stream($socket, $this->loop));
            return;
        }

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

    protected function getSocketUrl($host, $port)
    {
        return sprintf('tcp://%s:%s', $host, $port);
    }

}

