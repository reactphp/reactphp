<?php

namespace React\HttpClient;

use React\Socket\RemoteAddressInterface;

interface HttpAddressInterface extends RemoteAddressInterface
{
    public function getHttpAddress();
    public function isSecure();
}