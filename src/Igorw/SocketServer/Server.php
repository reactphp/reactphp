<?php

namespace Igorw\SocketServer;

use Evenement\EventEmitter;
use Igorw\SocketServer\EventLoop\LoopInterface;
use Igorw\SocketServer\EventLoop\StreamSelectLoop;
use Igorw\SocketServer\EventLoop\LibEventLoop;

class Server extends EventEmitter
{
    private $master;
    private $clients = array();
    private $loop;

    public function __construct($host, $port, LoopInterface $loop = null)
    {
        if (null === $loop) {
            if (function_exists('event_base_new')) {
                $loop = new LibEventLoop;
            } else {
                $loop = new StreamSelectLoop;
            }
        }

        $this->loop = $loop;

        $this->master = stream_socket_server("tcp://$host:$port", $errno, $errstr);
        if (false === $this->master) {
            throw new ConnectionException($errstr, $errno);
        }

        $that = $this;

        $this->loop->addReadStream($this->master, function ($master) use ($that) {
            $newSocket = stream_socket_accept($master);
            if (false === $newSocket) {
                $that->emit('error', array('Error accepting new connection'));
                continue;
            }
            $that->handleConnection($newSocket);
        });
    }

    public function addInput($name, $stream)
    {
        $that = $this;

        $this->loop->addReadStream($stream, function ($stream) use ($name, $that) {
            $that->emit("input.$name", array($stream));
        });
    }

    public function run()
    {
        $this->loop->run();
    }

    public function handleConnection($socket)
    {
        $client = $this->createConnection($socket);

        $this->clients[(int) $socket] = $client;

        $that = $this;

        $this->loop->addReadStream($socket, function ($stream) use ($that) {
            $data = @stream_socket_recvfrom($stream, 4096);
            if ($data === '') {
                $that->handleDisconnect($stream);
            } else {
                $that->handleData($stream, $data);
            }
        });

        $this->emit('connect', array($client));
    }

    public function handleDisconnect($socket)
    {
        $this->close($socket);
    }

    public function handleData($socket, $data)
    {
        $client = $this->getClient($socket);

        $client->emit('data', array($data));
    }

    public function getClient($socket)
    {
        return $this->clients[(int) $socket];
    }

    public function getClients()
    {
        return $this->clients;
    }

    public function write($data)
    {
        foreach ($this->clients as $conn) {
            $conn->write($data);
        }
    }

    public function close($socket)
    {
        $client = $this->getClient($socket);

        $client->emit('end');

        unset($this->clients[(int) $socket]);
        unset($client);

        fclose($socket);
    }

    public function getPort()
    {
        $name = stream_socket_get_name($this->master, false);
        return (int) substr(strrchr($name, ':'), 1);
    }

    public function shutdown()
    {
        $this->loop->removeStream($this->master);
        stream_socket_shutdown($this->master, STREAM_SHUT_RDWR);
    }

    public function createConnection($socket)
    {
        return new Connection($socket, $this);
    }
}
