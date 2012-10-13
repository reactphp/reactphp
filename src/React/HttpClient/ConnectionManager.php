<?php

namespace React\HttpClient;

use React\EventLoop\LoopInterface;
use React\Stream\Stream;
use React\Dns\Resolver\Resolver;

class ConnectionManager implements ConnectionManagerInterface
{
    protected $loop;
    protected $resolver;

    public function __construct(LoopInterface $loop, Resolver $resolver)
    {
        $this->loop = $loop;
        $this->resolver = $resolver;
    }

    public function getConnection($callback, $host, $port)
    {
        $that = $this;
        $this->resolve(function ($address, $error = null) use ($that, $callback, $host, $port) {
            if ($error) {
                call_user_func($callback, null, new \RuntimeException(
                    sprintf("failed to resolve %s", $host),
                    0,
                    $error
                ));
                return;
            }
            $that->getConnectionForAddress($callback, $address, $port);
        }, $host);
    }

    public function getConnectionForAddress($callback, $address, $port)
    {
        $url = $this->getSocketUrl($address, $port);

        $socket = stream_socket_client($url, $errno, $errstr, ini_get("default_socket_timeout"), STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

        if (!$socket) {
            call_user_func($callback, null, new \RuntimeException(
                sprintf("connection to %s:%d failed: %s", $addresss, $port, $errstr),
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

    protected function resolve($callback, $host)
    {
        if (false !== filter_var($host, FILTER_VALIDATE_IP)) {
            call_user_func($callback, $host);
            return;
        }

        $this->resolver->resolve($host, function ($address) use ($callback) {
            call_user_func($callback, $address);
        }, function ($error) use ($callback) {
            call_user_func($callback, null, $error);
        });
    }
}

