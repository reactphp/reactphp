<?php

namespace React\SocketClient;

use React\EventLoop\LoopInterface;
use React\Dns\Resolver\Resolver;
use React\Stream\Stream;
use React\Promise\When;
use React\Promise\Deferred;

class Connector implements ConnectorInterface
{
    private $loop;
    private $resolver;

    public function __construct(LoopInterface $loop, Resolver $resolver)
    {
        $this->loop = $loop;
        $this->resolver = $resolver;
    }

    public function createTcp($host, $port)
    {
        $that = $this;

        return $this
            ->resolveHostname($host)
            ->then(function ($address) use ($port, $that) {
                return $that->createSocketForAddress($address, $port);
            });
    }

    public function createUdp($host, $port)
    {
        $that = $this;

        return $this
            ->resolveHostname($host)
            ->then(function ($address) use ($port, $that) {
                return $that->createSocketForAddress($address, $port, 'udp');
            });
    }

    public function createSocketForAddress($address, $port, $transport = 'tcp')
    {
        $url = $this->getSocketUrl($address, $port, $transport);

        $socket = stream_socket_client($url, $errno, $errstr, 0, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

        if (!$socket) {
            return When::reject(new \RuntimeException(
                sprintf("connection to %s:%d failed: %s", $address, $port, $errstr),
                $errno
            ));
        }

        stream_set_blocking($socket, 0);

        // wait for connection

        return $this
            ->waitForStreamOnce($socket)
            ->then(array($this, 'checkConnectedSocket'))
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

    public function checkConnectedSocket($socket)
    {
        // The following hack looks like the only way to
        // detect connection refused errors with PHP's stream sockets.
        if (false === stream_socket_get_name($socket, true)) {
            return When::reject(new ConnectionException('Connection refused'));
        }

        return When::resolve($socket);
    }

    public function handleConnectedSocket($socket)
    {
        return new Stream($socket, $this->loop);
    }

    protected function getSocketUrl($host, $port, $transport)
    {
        return sprintf('%s://%s:%s', $transport, $host, $port);
    }

    protected function resolveHostname($host)
    {
        if (false !== filter_var($host, FILTER_VALIDATE_IP)) {
            return When::resolve($host);
        }

        return $this->resolver->resolve($host);
    }
}
