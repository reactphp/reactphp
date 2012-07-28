<?php

namespace React\Http\Client;

interface ConnectionManagerInterface
{
    public function getConnection($host, $port, $https = false);
}
