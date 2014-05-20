<?php

namespace React\Socket;

interface UnixAddressInterface extends AddressInterface
{
    public function getFilename();
}