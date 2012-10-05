<?php

namespace React\Http;

use Evenement\EventEmitter;
use Guzzle\Http\Message\Response as GuzzleResponse;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

class Response extends EventEmitter implements WritableStreamInterface
{
    private $writable = true;
    private $conn;
    private $headWritten = false;
    private $chunkedEncoding = true;

    public function __construct(ConnectionInterface $conn)
    {
        $this->conn = $conn;
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

        $response = new GuzzleResponse($status);
        $response->setHeader('X-Powered-By', 'React/alpha');
        $response->addHeaders($headers);
        if ($this->chunkedEncoding) {
            $response->setHeader('Transfer-Encoding', 'chunked');
        }
        $data = (string) $response;
        $this->conn->write($data);

        $this->headWritten = true;
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
        $this->removeAllListeners();
        $this->conn->end();
    }

    public function close()
    {
        $this->writable = false;
        $this->emit('end');
        $this->removeAllListeners();
        $this->conn->close();
    }
}
