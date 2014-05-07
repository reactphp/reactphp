<?php

namespace React\Socket;

interface AddressInterface
{
    public function __construct($address = null);
    public function __toString();
    public static function checkAddressType($address, &$error);
    public function getAddress();
    public function isValid();
}