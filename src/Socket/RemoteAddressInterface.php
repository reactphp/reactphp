<?php

namespace React\Socket;

interface RemoteAddressInterface extends AddressInterface
{
    public static function convert(RemoteAddressInterface $address);
    public function getHost();
    public function getPort();
    public function isIPv4();
    public function isIPv6();
    public function setHost($host);
    public function setPort($port);
}