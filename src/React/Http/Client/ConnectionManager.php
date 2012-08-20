<?php

namespace React\Http\Client;

use React\EventLoop\LoopInterface;
use React\Stream\Stream;

class ConnectionManager implements ConnectionManagerInterface
{
    protected $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function getConnection($callback, $host, $port)
    {
        $url = $this->getSocketUrl($host, $port);

        $socket = stream_socket_client($url, $errno, $errstr, ini_get("default_socket_timeout"), STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

        if (!$socket) {
            call_user_func($callback, null, new \RuntimeException(
                $errstr,
                $errno
            ));
            return;
        }

        stream_set_blocking($socket, 0);

        // wait for connection

        $loop = $this->loop;
        $that = $this;

        $this->loop->addWriteStream($socket, function () use ($that, $callback, $socket, $loop) {

            $loop->removeWriteStream($socket);

            $that->handleConnectedSocket($callback, $socket);
        });
    }

    public function handleConnectedSocket($callback, $socket)
    {
        call_user_func($callback, new Stream($socket, $this->loop));
    }

    protected function getSocketUrl($host, $port)
    {
        return sprintf('tcp://%s:%s', $host, $port);
    }

}

