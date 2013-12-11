<?php

namespace React\Socket\Client;

interface ConnectorInterface
{
    public function create($host, $port);
}
