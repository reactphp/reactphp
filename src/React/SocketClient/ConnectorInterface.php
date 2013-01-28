<?php

namespace React\SocketClient;

interface ConnectorInterface
{
    public function createTcp($host, $port);
}
