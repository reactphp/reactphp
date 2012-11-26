<?php

namespace React\HttpClient;

use React\EventLoop\LoopInterface;
use React\Stream\Stream;
use React\Promise\Deferred;
use React\Promise\ResolverInterface;

class SecureConnectionManager extends ConnectionManager
{
    public function handleConnectedSocket($socket)
    {
        $that = $this;

        $deferred = new Deferred();

        $enableCrypto = function () use ($that, $socket, $deferred) {
            $that->enableCrypto($socket, $deferred);
        };

        $this->loop->addWriteStream($socket, $enableCrypto);
        $this->loop->addReadStream($socket, $enableCrypto);
        $enableCrypto();

        return $deferred->promise();
    }

    public function enableCrypto($socket, ResolverInterface $resolver)
    {
        $result = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        if (true === $result) {
            $this->loop->removeWriteStream($socket);
            $this->loop->removeReadStream($socket);

            $resolver->resolve(new Stream($socket, $this->loop));
        } else if (false === $result) {
            $this->loop->removeWriteStream($socket);
            $this->loop->removeReadStream($socket);

            $resolver->reject();
        } else {
            // need more data, will retry
        }
    }
}
