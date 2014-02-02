<?php

namespace React\SocketClient;

interface ConnectorInterface
{
    public function create($host, $port);
}
