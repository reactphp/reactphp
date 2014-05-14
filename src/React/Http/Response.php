<?php

namespace React\Http;

use Evenement\EventEmitter;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

class Response extends EventEmitter implements WritableStreamInterface
{
    private $closed = false;
    private $writable = true;
    private $conn;
    private $headWritten = false;
    private $chunkedEncoding = true;
    private $connListeners;

    public function __construct(ConnectionInterface $conn)
    {
        $this->conn = $conn;
        
        $this->listenToConn();
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function writeContinue()
    {
        if ($this->headWritten) {
            throw new \Exception('Response head has already been written.');
        }

        $this->conn->write("HTTP/1.1 100 Continue\r\n");
    }

    public function writeHead($status = 200, array $headers = array())
    {
        if ($this->headWritten) {
            throw new \Exception('Response head has already been written.');
        }

        if (isset($headers['Content-Length'])) {
            $this->chunkedEncoding = false;
        }

        $headers = array_merge(
            array('X-Powered-By' => 'React/alpha'),
            $headers
        );
        if ($this->chunkedEncoding) {
            $headers['Transfer-Encoding'] = 'chunked';
        }

        $data = $this->formatHead($status, $headers);
        $this->conn->write($data);

        $this->headWritten = true;
    }

    private function formatHead($status, array $headers)
    {
        $status = (int) $status;
        $text = isset(ResponseCodes::$statusTexts[$status]) ? ResponseCodes::$statusTexts[$status] : '';
        $data = "HTTP/1.1 $status $text\r\n";

        foreach ($headers as $name => $value) {
            $name = str_replace(array("\r", "\n"), '', $name);
            $value = str_replace(array("\r", "\n"), '', $value);

            $data .= "$name: $value\r\n";
        }
        $data .= "\r\n";

        return $data;
    }

    public function write($data)
    {
        if (!$this->headWritten) {
            throw new \Exception('Response head has not yet been written.');
        }

        if ($this->chunkedEncoding) {
            $len = strlen($data);
            $chunk = dechex($len)."\r\n".$data."\r\n";
            $flushed = $this->conn->write($chunk);
        } else {
            $flushed = $this->conn->write($data);
        }

        return $flushed;
    }

    public function end($data = null)
    {
        if (null !== $data) {
            $this->write($data);
        }

        if ($this->chunkedEncoding) {
            $this->conn->write("0\r\n\r\n");
        }

        $this->emit('end');
        $this->stopListeningToConn();
        $this->removeAllListeners();
    }

    public function close()
    {
        if ($this->closed) {
            $this->removeAllListeners();
            return;
        }

        $this->closed = true;

        $this->writable = false;
        $this->emit('close');
        $this->stopListeningToConn();
        $this->removeAllListeners();
        $this->conn->close();
    }
    
    private function listenToConn()
    {
        $response = $this;
        
        $this->connListeners = array(
            'end'   => function () use ($response) {
                $response->close();
            },
            'error' => function ($error) use ($response) {
                $response->emit('error', array($error, $response));
                $response->close();
            },
            'drain' => function () use ($response) {
                $response->emit('drain');
            },
        );
        
        foreach ($this->connListeners as $event => $listener) {
            $this->conn->on($event, $listener);
        }
    }
    
    private function stopListeningToConn()
    {
        foreach ($this->connListeners as $event => $listener) {
            $this->conn->removeListener($event, $listener);
        }
        
        $this->connListeners = array();
    }
}