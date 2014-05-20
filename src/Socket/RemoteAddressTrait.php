<?php

namespace React\Socket;

trait RemoteAddressTrait
{
    /**
     * Address of the socket.
     */
    protected $address;

    /**
     * Host name of the socket.
     */
    protected $host;

    /**
     * Port of the socket.
     */
    protected $port;

    /**
     * Is an IPv6 address?
     */
    protected $isIPv6;

    public function __construct($address = null)
    {
        if ($address === null) return;

        preg_match(static::EXPRESSION, $address, $matches);

        $this->host = trim($matches['host'], '[]');

        if (isset($matches['port'])) {
            $this->port = $matches['port'];
        }

        $this->buildAddress();
    }

    public function __toString()
    {
        return (string)$this->address;
    }

    protected function buildAddress() {
        $this->address = static::SCHEME . "{$this->host}";
        $this->isIPv6 = false;

        if (false !== strpos($this->host, ':')) {
            $this->address = static::SCHEME . "[{$this->host}]";
            $this->isIPv6 = true;
        }

        if (isset($this->port)) {
            $this->address .= ":{$this->port}";
        }
    }

    public static function checkAddressType($address, &$error)
    {
        $result = (boolean)preg_match(static::EXPRESSION, $address, $matches);

        if (false === $result && 0 === strpos($address, static::SCHEME)) {
            $error = new AddressException("Invalid address '{$address}', missing host name.");
        }

        return $result;
    }

    public static function convert(RemoteAddressInterface $address)
    {
        $new = new static();
        $new->setHost($address->getHost());
        $new->setPort($address->getPort());

        return $new;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function isIPv4()
    {
        return !$this->isIPv6;
    }

    public function isIPv6()
    {
        return $this->isIPv6;
    }

    public function isValid()
    {
        return isset($this->host);
    }

    public function setHost($host)
    {
        $this->host = $host;
        $this->buildAddress();
    }

    public function setPort($port)
    {
        $this->port = $port;
        $this->buildAddress();
    }
}