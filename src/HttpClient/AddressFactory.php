<?php

namespace React\HttpClient;
use Exception;
use React\Socket\AddressException;

class AddressFactory
{
    public static function create($address)
    {
        if (HttpAddress::checkAddressType($address, $error)) {
            return new HttpAddress($address);
        }

        else if ($error instanceof Exception) {
            throw $error;
        }

        else {
            throw new AddressException("Invalid address '{$address}'.");
        }
    }
}