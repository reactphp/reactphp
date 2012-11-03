<?php

namespace React\HttpClient;

interface ConnectionManagerInterface
{
    public function getConnection($callback, $host, $port);
}
