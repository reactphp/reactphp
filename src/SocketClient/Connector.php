<?php

namespace React\SocketClient;

use React\EventLoop\LoopInterface;
use React\Dns\Resolver\Resolver;
use React\Socket\AddressFactory;
use React\Socket\AddressInterface;
use React\Socket\TcpAddressInterface;
use React\Stream\Stream;
use React\Promise;
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

    public function create($address)
    {
        $address = AddressFactory::create($address);

        return $this
            ->resolveHostname($address)
            ->then(function($resolved) use ($address) {
                // Update TCP address with resolved IP address:
                if ($address instanceof TcpAddressInterface && false === ($resolved instanceof TcpAddressInterface)) {
                    $address->setHost($resolved);
                }

                return $this->createSocketForAddress($address);
            });
    }

    public function createSocketForAddress($address)
    {
        $socket = stream_socket_client($address, $errno, $errstr, 0, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);

        if (!$socket) {
            return Promise\reject(new \RuntimeException(
                sprintf("connection to %s failed: %s", $address, $errstr),
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
            return Promise\reject(new ConnectionException('Connection refused'));
        }

        return Promise\resolve($socket);
    }

    public function handleConnectedSocket($socket)
    {
        return new Stream($socket, $this->loop);
    }

    protected function resolveHostname($address)
    {
        if (false === ($address instanceof TcpAddressInterface)) {
            return Promise\resolve($address);
        }

        if (false !== filter_var($address->getHost(), FILTER_VALIDATE_IP)) {
            return Promise\resolve($address);
        }

        return $this->resolver->resolve($address->getHost());
    }
}
