<?php

namespace React\Cache;

interface CacheInterface
{
    // @return React\Promise\PromiseInterface
    public function get($key);

    public function set($key, $value);

    public function remove($key);
}
