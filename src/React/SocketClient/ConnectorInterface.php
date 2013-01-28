<?php

namespace React\SocketClient;

interface ConnectorInterface
{
    public function getConnection($host, $port);
}
