<?php

namespace React\Socket;

trait AddressTrait
{
    /**
     * Address of the socket.
     */
    protected $address;

    /**
     * Filename of the socket (for UDS).
     */
    protected $filename;

    /**
     * Host name of the socket (for TCP).
     */
    protected $host;

    /**
     * Port of the socket (for TCP).
     */
    protected $port;

    public function getAddress()
    {
        return $this->address;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function listen($port, $host = '127.0.0.1')
    {
        // enclose IPv6 addresses in square brackets before appending port
        if (strpos($host, ':') !== false) {
            $host = '[' . $host . ']';
        }

        $this->setAddress("tcp://{$host}:{$port}");
        $this->createSocket();
    }

    public function listenAddress($address)
    {
        $this->setAddress($address);
        $this->createSocket();
    }

    protected function parseAddress($address)
    {
        preg_match('%^(?<address>(?<scheme>.+?)://(?<resource>.+))$%', $address, $matches);

        return (object)$matches;
    }

    protected function setAddress($address)
    {
        $data = $this->parseAddress($address);
        $this->address = $this->filename = null;

        if (false === isset($data->scheme, $data->resource)) {
            throw new AddressException('Could not parse given address, expecting either a tcp:// or unix:// resource.');
        }

        else if ('unix' === $data->scheme) {
            if ('WINNT' === PHP_OS) {
                throw new \RuntimeException("Unix sockets are unavailable on Windows");
            }

            $filename = substr($address, 7);
            $this->address = $address;

            // Relative to root:
            if (0 === strpos($filename, '/')) {
                $this->filename = $filename;
            }

            // Relative to current path:
            else {
                $this->filename = getcwd() . '/' . $filename;
            }
        }

        else if ('tcp' === $data->scheme) {
            preg_match('%(?<host>.+?):(?<port>[0-9]+)$%', $address, $matches);

            if (isset($matches['host'], $matches['port'])) {
                $this->host = $matches['host'];
                $this->port = $matches['port'];
            }

            $this->address = $address;
        }

        else {
            throw new AddressException('Invalid address schema, expecting either a tcp:// or unix:// resource.');
        }
    }
}