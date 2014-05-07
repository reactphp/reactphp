<?php

namespace React\Socket;
use Exception;

class AddressFactory
{
    public static function create($address)
    {
        if ($address instanceof AddressInterface) {
            return $address;
        }

        else if (TcpAddress::checkAddressType($address, $error)) {
            return new TcpAddress($address);
        }

        else if (UnixAddress::checkAddressType($address, $error)) {
            return new UnixAddress($address);
        }

        else if ($error instanceof Exception) {
            throw $error;
        }

        else {
            throw new AddressException("Invalid address '{$address}'.");
        }
    }
}