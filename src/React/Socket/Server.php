<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

/** @event connection */
class Server extends EventEmitter implements ServerInterface
{
    public  $master;
    private $loop;
    private $context;

    public function __construct(LoopInterface $loop, array $context = array())
    {
        $this->loop = $loop;
        $this->context = $context;
    }

    public function listen($port, $host = '127.0.0.1')
    {
        $this->master = @stream_socket_server("tcp://$host:$port", $errno, $errstr);
        if (false === $this->master) {
            $message = "Could not bind to tcp://$host:$port: $errstr";
            throw new ConnectionException($message, $errno);
        }
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
        stream_context_set_option($socket, $this->context);
        stream_set_blocking($socket, 0);

        $client = $this->createConnection($socket);

        if ($client instanceof SecureConnection) {
            $self = $this;
            $client->on('connection', function($client) use ($self) {
                $self->emit('connection', array($client));
            });
        } else {
            $this->emit('connection', array($client));
        }
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
        $context = stream_context_get_options($socket);
        if (array_key_exists('ssl', $context)) {
            $conn = new SecureConnection($socket, $this->loop);
        } else {
            $conn = new Connection($socket, $this->loop);
        }

        return $conn;
    }
}
