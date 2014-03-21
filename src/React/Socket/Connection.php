<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Buffer;
use React\Stream\Stream;
use React\Stream\Util;

class Connection extends Stream implements ConnectionInterface
{
    public function handleData($stream)
    {
        $data = stream_socket_recvfrom($stream, $this->bufferSize);
        if ('' === $data || false === $data || feof($stream)) {
            $this->end();
        } else {
            $this->emit('data', array($data, $this));
        }
    }

    public function handleClose()
    {
        if (is_resource($this->stream)) {
            // http://chat.stackoverflow.com/transcript/message/7727858#7727858
            stream_socket_shutdown($this->stream, STREAM_SHUT_RDWR);
            stream_set_blocking($this->stream, false);
            fclose($this->stream);
        }
    }

    public function getRemoteAddress()
    {
        return $this->parseAddress(stream_socket_get_name($this->stream, true));
    }

    private function parseAddress($address)
    {
        return trim(substr($address, 0, strrpos($address, ':')), '[]');
    }
}
