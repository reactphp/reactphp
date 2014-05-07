<?php

namespace React\HttpClient;
use React\Socket\AddressException;
use React\Socket\RemoteAddressTrait;

class HttpAddress implements HttpAddressInterface
{
    use RemoteAddressTrait {
        RemoteAddressTrait::buildAddress as buildTcpAddress;
    }

    const SCHEME = 'tcp://';
    const EXPRESSION = '%^(?<scheme>https?)://(?<host>.+?)(:(?<port>[0-9]+))?(?<path>/.+)?$%';

    const SECURE_PORT = 443;
    const STANDARD_PORT = 80;

    /**
     * The unaltered HTTP address.
     */
    protected $httpAddress;

    /**
     * Does this address represent a secure connection?
     */
    protected $secure;

    public function __construct($address = null)
    {
        if ($address === null) return;

        preg_match(static::EXPRESSION, $address, $matches);

        $this->host = trim($matches['host'], '[]');
        $this->httpAddress = $address;

        if ($matches['scheme'] === 'https') {
            $this->port = static::SECURE_PORT;
            $this->secure = true;
        }

        else {
            $this->port = static::STANDARD_PORT;
            $this->secure = false;
        }

        if (isset($matches['port']) && false === empty($matches['port'])) {
            $this->port = (integer)$matches['port'];
        }

        $this->buildAddress();
    }

    public static function checkAddressType($address, &$error)
    {
        $result = (boolean)preg_match(static::EXPRESSION, $address, $matches);

        if (false === $result && preg_match('%^https?://%', $address)) {
            $error = new AddressException("Invalid address '{$address}', missing host name.");
        }

        return $result;
    }

    public function getHttpAddress()
    {
        return $this->httpAddress;
    }

    public function isSecure()
    {
        return $this->secure;
    }

    public function setPort($port)
    {
        if (empty($port)) {
            $port = static::STANDARD_PORT;
        }

        $this->port = (integer)$port;
        $this->buildAddress();
    }
}