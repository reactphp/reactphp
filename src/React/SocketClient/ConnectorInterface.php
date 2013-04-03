<?php

namespace React\SocketClient;

interface ConnectorInterface
{
    public function createTcp($host, $port);
    public function createUdp($host, $port);
}
