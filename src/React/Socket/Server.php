<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

/** @event connection */
class Server extends EventEmitter implements ServerInterface
{
    public $master;
    private $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function listen($port, $host = '127.0.0.1')
    {
        $socket = @stream_socket_server("tcp://$host:$port", $errno, $errstr);
        if (false === $socket) {
            $message = "Could not bind to tcp://$host:$port: $errstr";
            throw new ConnectionException($message, $errno);
        }

        $this->handleServerConnection($socket);
    }

    public function listenUnix($descriptor, $type = 'unix')
    {
        if ('WINNT' === PHP_OS) {
            throw new \RuntimeException("Unix sockets are unavailable on Windows");
        }

        if ($type !== 'unix' && $type !== 'udg') {
            throw new \InvalidArgumentException("{$type} must be 'unix' or udg'");
        }

        $socket = @stream_socket_server("{$type}://$descriptor", $errno, $errstr);
        if (false === $socket) {
            throw new ConnectionException("Could not open file descriptor for unix socket {$descriptor}: $errstr", $errno);
        }

        $this->handleServerConnection($socket);
    }

    private function handleServerConnection($socket)
    {
        $this->master = $socket;

        stream_set_blocking($this->master, 0);

        $that = $this;

        $this->loop->addReadStream($this->master, function ($master) use ($that) {
            $newSocket = stream_socket_accept($master);
            if (false === $newSocket) {
                $that->emit('error', array(new \RuntimeException('Error accepting new connection')));

                return;
            }
            $that->handleConnection($newSocket);
        });
    }

    public function handleConnection($socket)
    {
        stream_set_blocking($socket, 0);

        $client = $this->createConnection($socket);

        $this->emit('connection', array($client));
    }

    public function getPort()
    {
        $name = stream_socket_get_name($this->master, false);

        return (int) substr(strrchr($name, ':'), 1);
    }

    public function shutdown()
    {
        $this->loop->removeStream($this->master);
        fclose($this->master);
    }

    public function createConnection($socket)
    {
        return new Connection($socket, $this->loop);
    }
}
