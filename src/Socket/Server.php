<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

/** @event connection(Connection $client) */
/** @event error(RuntimeException $error) */
class Server extends EventEmitter implements ServerInterface
{
    protected $address;
    public $master;
    protected $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function listen($address)
    {
        $this->address = AddressFactory::create($address);

        // Unfortunately there does not appear to be a good way to check
        // if a unix socket file is still active, as a result we will unlink
        // the socket file before attempting to connect. This will leave any
        // previous server using the socket unreachable but still running.
        if (
            $this->address instanceof UnixAddressInterface
            && file_exists($this->address->getFilename())
        ) {
            unlink($this->address->getFilename());
        }

        $this->master = @stream_socket_server($this->address, $errno, $errstr);

        if (false === $this->master) {
            $message = "Could not bind to {$this->address}: {$errstr}";
            throw new ConnectionException($message, $errno);
        }

        stream_set_blocking($this->master, 0);

        $this->loop->addReadStream($this->master, function ($master) {
            $newSocket = stream_socket_accept($master);
            if (false === $newSocket) {
                $this->emit('error', array(new \RuntimeException('Error accepting new connection')));

                return;
            }
            $this->handleConnection($newSocket);
        });
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function handleConnection($socket)
    {
        stream_set_blocking($socket, 0);

        $client = $this->createConnection($socket);

        $this->emit('connection', array($client));
    }

    public function shutdown()
    {
        if (is_resource($this->master)) {
            $this->loop->removeStream($this->master);
            fclose($this->master);
        }

        $this->removeAllListeners();

        if (
            $this->address instanceof UnixAddressInterface
            && file_exists($this->address->getFilename())
        ) {
            unlink($this->address->getFilename());
        }
    }

    public function createConnection($socket)
    {
        return new Connection($socket, $this->loop);
    }
}
