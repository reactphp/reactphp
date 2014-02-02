<?php

namespace React\SocketClient;

use React\Promise\Deferred;
use React\Stream\Stream;
use React\EventLoop\LoopInterface;
use UnexpectedValueException;

/**
 * This class is considered internal and its API should not be relied upon
 * outside of SocketClient
 */
class StreamEncryption
{
    private $loop;
    private $method = STREAM_CRYPTO_METHOD_TLS_CLIENT;

    private $errstr;
    private $errno;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function enable(Stream $stream)
    {
        return $this->toggle($stream, true);
    }

    public function disable(Stream $stream)
    {
        return $this->toggle($stream, false);
    }

    public function toggle(Stream $stream, $toggle)
    {
        // pause actual stream instance to continue operation on raw stream socket
        $stream->pause();

        // TODO: add write() event to make sure we're not sending any excessive data

        $deferred = new Deferred();

        // get actual stream socket from stream instance
        $socket = $stream->stream;

        $toggleCrypto = function () use ($socket, $deferred, $toggle) {
            $this->toggleCrypto($socket, $deferred, $toggle);
        };

        $this->loop->addWriteStream($socket, $toggleCrypto);
        $this->loop->addReadStream($socket, $toggleCrypto);
        $toggleCrypto();

        return $deferred->promise()->then(function () use ($stream) {
            $stream->resume();
            return $stream;
        }, function($error) use ($stream) {
            $stream->resume();
            throw $error;
        });
    }

    public function toggleCrypto($socket, Deferred $deferred, $toggle)
    {
        set_error_handler(array($this, 'handleError'));
        $result = stream_socket_enable_crypto($socket, $toggle, $this->method);
        restore_error_handler();

        if (true === $result) {
            $this->loop->removeWriteStream($socket);
            $this->loop->removeReadStream($socket);

            $deferred->resolve();
        } else if (false === $result) {
            $this->loop->removeWriteStream($socket);
            $this->loop->removeReadStream($socket);

            $deferred->reject(new UnexpectedValueException(
                sprintf("Unable to complete SSL/TLS handshake: %s", $this->errstr),
                $this->errno
            ));
        } else {
            // need more data, will retry
        }
    }

    public function handleError($errno, $errstr)
    {
        $this->errstr = str_replace(array("\r", "\n"), ' ', $errstr);
        $this->errno  = $errno;
    }
}
