<?php

namespace React\SocketClient;

interface ConnectionManagerInterface
{
    public function getConnection($host, $port);
}
