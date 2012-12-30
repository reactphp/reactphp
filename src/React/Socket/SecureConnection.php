<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Stream;
use React\Stream\Util;

/** @events connection */
class SecureConnection extends Connection {
    protected $secure = false;

    public function handleData($stream) {
        if (!$this->secure) {
            $result = stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_TLS_SERVER);

            if (0 === $result) {
                return;
            }

            if (false === $result) {
                echo "error\n";
                return;
            }

            $this->secure = true;
            $this->emit('connection', array($this));
        }

        parent::handleData($stream);
    }
}
