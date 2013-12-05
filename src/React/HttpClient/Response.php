<?php

namespace React\HttpClient;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\Stream;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

class Response extends EventEmitter implements ReadableStreamInterface
{
    private $stream;
    private $protocol;
    private $version;
    private $code;
    private $reasonPhrase;
    private $headers;
    private $readable = true;

    public function __construct(Stream $stream, $protocol, $version, $code, $reasonPhrase, $headers)
    {
        $this->stream = $stream;
        $this->protocol = $protocol;
        $this->version = $version;
        $this->code = $code;
        $this->reasonPhrase = $reasonPhrase;
        $this->headers = $headers;

        $stream->on('data', array($this, 'handleData'));
        $stream->on('error', array($this, 'handleError'));
        $stream->on('end', array($this, 'handleEnd'));
    }

    public function getProtocol()
    {
        return $this->protocol;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function handleData($data)
    {
        $this->emit('data', array($data, $this));
    }

    public function handleEnd()
    {
        $this->close();
    }

    public function handleError(\Exception $error)
    {
        $this->emit('error', array(new \RuntimeException(
            "An error occurred in the underlying stream",
            0,
            $error
        ), $this));

        $this->close($error);
    }

    public function close(\Exception $error = null)
    {
        if (!$this->readable) {
            return;
        }

        $this->readable = false;

        $this->emit('end', array($error, $this));

        $this->removeAllListeners();
        $this->stream->end();
    }

    public function isReadable()
    {
        return $this->readable;
    }

    public function pause()
    {
        if (!$this->readable) {
            return;
        }

        $this->stream->pause();
    }

    public function resume()
    {
        if (!$this->readable) {
            return;
        }

        $this->stream->resume();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }
}

