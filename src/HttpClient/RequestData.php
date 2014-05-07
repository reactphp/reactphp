<?php

namespace React\HttpClient;

class RequestData
{
    private $method;
    private $address;
    private $headers;

    private $protocolVersion = '1.1';

    public function __construct($method, $address, array $headers = array())
    {
        $this->method = $method;
        $this->address = AddressFactory::create($address);
        $this->headers = $headers;
    }

    private function mergeDefaultheaders(array $headers)
    {
        $port = ($this->getDefaultPort() === $this->getPort()) ? '' : ':' . $this->getPort();
        $connectionHeaders = ('1.1' === $this->protocolVersion) ? array('Connection' => 'close') : array();

        return array_merge(
            array(
                'Host'          => $this->getHost().$port,
                'User-Agent'    => 'React/alpha',
            ),
            $connectionHeaders,
            $headers
        );
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getScheme()
    {
        $url = $this->address->getHttpAddress();

        return parse_url($url, PHP_URL_SCHEME);
    }

    public function getHost()
    {
        return $this->address->getHost();
    }

    public function getPort()
    {
        return $this->address->getPort();
    }

    public function getDefaultPort()
    {
        return ($this->address->isSecure())
            ? HttpAddress::SECURE_PORT
            : HttpAddress::STANDARD_PORT;
    }

    public function getPath()
    {
        $url = $this->address->getHttpAddress();
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $queryString = parse_url($url, PHP_URL_QUERY);

        return $path.($queryString ? "?$queryString" : '');
    }

    public function setProtocolVersion($version)
    {
        $this->protocolVersion = $version;
    }

    public function __toString()
    {
        $headers = $this->mergeDefaultheaders($this->headers);

        $data = '';
        $data .= "{$this->method} {$this->getPath()} HTTP/{$this->protocolVersion}\r\n";
        foreach ($headers as $name => $value) {
            $data .= "$name: $value\r\n";
        }
        $data .= "\r\n";

        return $data;
    }
}
