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
        $url = $this->getSocketUrl($host, $port, $https);

        $socket = stream_socket_client($url, $errno, $errstr, ini_get("default_socket_timeout"), STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

        if (!$socket) {
            call_user_func($callback, null);
        }

        // wait for connection

        $loop = $this->loop;

        $this->loop->addWriteStream($socket, function() use ($callback, $socket, $loop) {
            $loop->removeWriteStream($socket);
            $callback(new Stream($socket, $this->loop));
        });
    }

    protected function getSocketUrl($host, $port, $https)
    {
        $scheme = $https ? 'tls' : 'tcp';
        return sprintf('%s://%s:%s' , $scheme, $host, $port);
    }

}

