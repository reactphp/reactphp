<?php

namespace React\SocketClient;

use React\EventLoop\LoopInterface;
use React\Dns\Resolver\Resolver;
use React\Stream\Stream;
use React\Promise\Deferred;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;

class ConnectionManager implements ConnectionManagerInterface
{
    protected $loop;
    protected $resolver;

    public function __construct(LoopInterface $loop, Resolver $resolver)
    {
        $this->loop = $loop;
        $this->resolver = $resolver;
    }

    public function getConnection($host, $port)
    {
        $that = $this;

        return $this
            ->resolveHostname($host)
            ->then(function ($address) use ($port, $that) {
                return $that->getConnectionForAddress($address, $port);
            });
    }

    public function getConnectionForAddress($address, $port)
    {
        $url = $this->getSocketUrl($address, $port);

        $socket = stream_socket_client($url, $errno, $errstr, ini_get("default_socket_timeout"), STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

        if (!$socket) {
            return new RejectedPromise(new \RuntimeException(
                sprintf("connection to %s:%d failed: %s", $address, $port, $errstr),
                $errno
            ));
        }

        stream_set_blocking($socket, 0);

        // wait for connection

        return $this
            ->waitForStreamOnce($socket)
            ->then(array($this, 'handleConnectedSocket'));
    }

    protected function waitForStreamOnce($stream)
    {
        $deferred = new Deferred();

        $loop = $this->loop;

        $this->loop->addWriteStream($stream, function ($stream) use ($loop, $deferred) {
            $loop->removeWriteStream($stream);
            $deferred->resolve($stream);
        });

        return $deferred->promise();
    }

    public function handleConnectedSocket($socket)
    {
        if (false === stream_socket_get_name($socket, true)) {
            $e = new ConnectionException('Connection refused');
            return new RejectedPromise($e);
        }

        return new Stream($socket, $this->loop);
    }

    protected function getSocketUrl($host, $port)
    {
        return sprintf('tcp://%s:%s', $host, $port);
    }

    protected function resolveHostname($host)
    {
        if (false !== filter_var($host, FILTER_VALIDATE_IP)) {
            return new FulfilledPromise($host);
        }

        return $this->resolver->resolve($host);
    }
}
