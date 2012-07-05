<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Stream\WritableStream;
use React\Stream\Buffer;
use React\Stream\Util;

class Connection extends EventEmitter implements ConnectionInterface
{
    public $bufferSize = 4096;
    public $socket;
    private $readable = true;
    private $writable = true;
    private $closing = false;
    private $loop;
    private $buffer;

    public function __construct($socket, LoopInterface $loop)
    {
        $this->socket = $socket;
        $this->loop = $loop;
        $this->buffer = new Buffer($this->socket, $this->loop);

        $that = $this;

        $this->buffer->on('error', function ($error) use ($that) {
            $that->emit('error', array($error, $that));
            $that->close();
        });

        $this->buffer->on('drain', function () use ($that) {
            $that->emit('drain');
        });

        $this->resume();
    }

    public function isReadable()
    {
        return $this->readable;
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function pause()
    {
        $this->loop->removeReadStream($this->socket);
    }

    public function resume()
    {
        $this->loop->addReadStream($this->socket, array($this, 'handleData'));
    }

    public function write($data)
    {
        if (!$this->writable) {
            return;
        }

        return $this->buffer->write($data);
    }

    public function close()
    {
        if (!$this->writable && !$this->closing) {
            return;
        }

        $this->closing = false;

        $this->readable = false;
        $this->writable = false;

        $this->emit('end', array($this));
        $this->emit('close', array($this));
        $this->loop->removeStream($this->socket);
        $this->buffer->removeAllListeners();
        $this->removeAllListeners();
        if (is_resource($this->socket)) {
            stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
            fclose($this->socket);
        }
    }

    public function end($data = null)
    {
        if (!$this->writable) {
            return;
        }

        $this->closing = true;

        $this->readable = false;
        $this->writable = false;

        $that = $this;

        $this->buffer->on('close', function () use ($that) {
            $that->close();
        });

        $this->buffer->end($data);
    }

    public function pipe(WritableStream $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }

    public function handleData($socket)
    {
        $data = stream_socket_recvfrom($socket, $this->bufferSize);
        if ('' === $data || false === $data) {
            $this->end();
        } else {
            $this->emit('data', array($data, $this));
        }
    }

    public function getBuffer()
    {
        return $this->buffer;
    }

    public function getRemoteAddress()
    {
        return $this->parseAddress(stream_socket_get_name($this->socket, true));
    }

    private function parseAddress($address)
    {
        return trim(substr($address, 0, strrpos($address, ':')), '[]');
    }
}
