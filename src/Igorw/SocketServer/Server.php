<?php

namespace Igorw\SocketServer;

use Evenement\EventEmitter;

class Server extends EventEmitter
{
    private $master;
    private $input;
    private $timeout;
    private $sockets = array();
    private $clients = array();

    // timeout = microseconds
    public function __construct($host, $port, $input = null, $timeout = 1000000)
    {
        $this->master = stream_socket_server("tcp://$host:$port", $errno, $errstr);
        if (false === $this->master) {
            throw new ConnectionException($errstr, $errno);
        }

        $this->sockets[] = $this->master;

        $this->input = $input;
        if (null !== $this->input) {
            $this->sockets[] = $this->input;
        }

        $this->timeout = $timeout;
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
        $readySockets = $this->sockets;
        @stream_select($readySockets, $write = null, $except = null, 0, $this->timeout);
        foreach ($readySockets as $socket) {
            if ($this->master === $socket) {
                $newSocket = stream_socket_accept($this->master);
                if (false === $newSocket) {
                    $this->emit('error', array('Error accepting new connection'));
                    continue;
                }
                $this->handleConnection($newSocket);
            } elseif (null !== $this->input && $this->input === $socket) {
                $this->handleInput($socket);
            } else {
                $data = @stream_socket_recvfrom($socket, 4096);
                if ($data === '') {
                    $this->handleDisconnect($socket);
                } else {
                    $this->handleData($socket, $data);
                }
            }
        };
    }

    private function handleConnection($socket)
    {
        $client = $this->createConnection($socket);

        $this->clients[(int) $socket] = $client;
        $this->sockets[] = $socket;

        $this->emit('connect', array($client));
    }

    private function handleInput($input)
    {
        $this->emit('input', array($input));
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

        $index = array_search($socket, $this->sockets);
        unset($this->sockets[$index]);

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
