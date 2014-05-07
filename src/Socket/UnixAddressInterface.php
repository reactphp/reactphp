<?php

namespace React\Socket;

interface TcpAddressInterface extends AddressInterface
{
    public function getFilename();
}