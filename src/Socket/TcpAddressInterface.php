<?php

namespace React\Socket;

interface TcpAddressInterface extends AddressInterface
{
    public function __construct($address);
    public function __toString();
    public function getAddress();
    public function getHost();
    public function getPort();
    public function setHost($host);
    public function setPort($port);
}