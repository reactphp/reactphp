<?php

namespace React\Socket;

class TcpAddress implements RemoteAddressInterface
{
    use RemoteAddressTrait;

    const SCHEME = 'tcp://';
    const EXPRESSION = '%^tcp://(?<host>.+?)(:(?<port>[0-9]+))?$%';

    public function toUdpAddress() {
        return UdpAddress::convert($this);
    }
}