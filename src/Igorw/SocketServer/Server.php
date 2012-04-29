<?php

namespace Igorw\SocketServer;

use Evenement\EventEmitter;
use Igorw\SocketServer\EventLoop\LoopInterface;
use Igorw\SocketServer\EventLoop\Factory;

class Server extends EventEmitter
{
    private $master;
    private $clients = array();
    private $loop;

    public $bufferSize = 4096;

    public function __construct($host, $port, LoopInterface $loop = null)
    {
        $this->loop = $loop ?: Factory::create();

        $this->master = stream_socket_server("tcp://$host:$port", $errno, $errstr);
        if (false === $this->master) {
            throw new ConnectionException($errstr, $errno);
        }
        stream_set_blocking($this->master, 0);

        $that = $this;

        $this->loop->addReadStream($this->master, function ($master) use ($that) {
            $newSocket = stream_socket_accept($master);
            if (false === $newSocket) {
                $that->emit('error', array('Error accepting new connection'));
                return;
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
        stream_set_blocking($socket, 0);
        $client = $this->createConnection($socket);

        $this->clients[(int) $socket] = $client;

        $this->loop->addReadStream($socket, array($this, 'handleData'));

        $this->emit('connect', array($client));
    }

    public function handleDisconnect($socket)
    {
        $this->close($socket);
    }

    public function handleData($socket)
    {
        $data = @stream_socket_recvfrom($socket, $this->bufferSize);
        if ('' === $data || false === $data) {
            $this->handleDisconnect($socket);
            $this->loop->removeStream($socket);
        } else {
            $client = $this->getClient($socket);
            $client->emit('data', array($data));
        }
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
