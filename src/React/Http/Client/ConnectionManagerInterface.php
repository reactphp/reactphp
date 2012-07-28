<?php

namespace React\Http\Client;

interface ConnectionManagerInterface
{
    public function getConnection($callback, $host, $port, $https = false);
}
