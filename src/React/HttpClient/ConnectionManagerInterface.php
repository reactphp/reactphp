<?php

namespace React\HttpClient;

interface ConnectionManagerInterface
{
    public function getConnection($host, $port);
}
