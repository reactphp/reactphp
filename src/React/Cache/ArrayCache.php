<?php

namespace React\Cache;

use React\Promise\When;

class ArrayCache implements CacheInterface
{
    private $data = array();

    public function get($key)
    {
        if (!isset($this->data[$key])) {
            return When::reject();
        }

        return When::resolve($this->data[$key]);
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function remove($key)
    {
        unset($this->data[$key]);
    }
}
