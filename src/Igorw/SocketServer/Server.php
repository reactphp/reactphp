<?php

namespace Igorw\SocketServer;

use Evenement\EventEmitter;

class Server extends EventEmitter
{
    private $master;
    private $timeout;
    private $inputs = array();
    private $streams = array();
    private $clients = array();

    // timeout = microseconds
    public function __construct($host, $port, $timeout = 1000000)
    {
        $this->master = stream_socket_server("tcp://$host:$port", $errno, $errstr);
        if (false === $this->master) {
            throw new ConnectionException($errstr, $errno);
        }

        $this->streams[] = $this->master;

        $this->timeout = $timeout;
    }

    public function addInput($name, $stream)
    {
        $this->inputs[$name] = $stream;
        $this->streams[] = $stream;
    }

    public function run()
    {
        // @codeCoverageIgnoreStart
        while (true) {
            $this->tick();
        }
        // @codeCoverageIgnoreEnd
    }

    public function tick()
    {
        $readyStreams = $this->streams;
        @stream_select($readyStreams, $write = null, $except = null, 0, $this->timeout);
        foreach ($readyStreams as $stream) {
            if ($this->master === $stream) {
                $newSocket = stream_socket_accept($this->master);
                if (false === $newSocket) {
                    $this->emit('error', array('Error accepting new connection'));
                    continue;
                }
                $this->handleConnection($newSocket);
            } elseif (in_array($stream, $this->inputs)) {
                $this->handleInput($stream);
            } else {
                $data = @stream_socket_recvfrom($stream, 4096);
                if ($data === '') {
                    $this->handleDisconnect($stream);
                } else {
                    $this->handleData($stream, $data);
                }
            }
        };
    }

    private function handleConnection($socket)
    {
        $client = $this->createConnection($socket);

        $this->clients[(int) $socket] = $client;
        $this->streams[] = $socket;

        $this->emit('connect', array($client));
    }

    private function handleInput($stream)
    {
        $name = array_search($stream, $this->inputs);
        if (false !== $name) {
            $this->emit("input.$name", array($stream));
        }
    }

    private function handleDisconnect($socket)
    {
        $this->close($socket);
    }

    private function handleData($socket, $data)
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

        $index = array_search($socket, $this->streams);
        unset($this->streams[$index]);

        fclose($socket);
    }

    public function getPort()
    {
        $name = stream_socket_get_name($this->master, false);
        return (int) substr(strrchr($name, ':'), 1);
    }

    public function shutdown()
    {
        stream_socket_shutdown($this->master, STREAM_SHUT_RDWR);
    }

    public function createConnection($socket)
    {
        return new Connection($socket, $this);
    }
}
