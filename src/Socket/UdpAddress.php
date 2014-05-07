<?php

namespace React\Socket;

class UdpAddress implements RemoteAddressInterface
{
    use RemoteAddressTrait;

    const SCHEME = 'udp://';
    const EXPRESSION = '%^udp://(?<host>.+?)(:(?<port>[0-9]+))?$%';

    public function toTcpAddress() {
        return TcpAddress::convert($this);
    }
}