<?php

namespace React\Dns\Model;

class HeaderBag
{
    public $data = '';

    public $attributes = array(
        'qdCount'   => 0,
        'anCount'   => 0,
        'nsCount'   => 0,
        'arCount'   => 0,
        'qr'        => 0,
        'opcode'    => Message::OPCODE_QUERY,
        'aa'        => 0,
        'tc'        => 0,
        'rd'        => 0,
        'ra'        => 0,
        'z'         => 0,
        'rcode'     => Message::RCODE_OK,
    );

    public function get($name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    public function set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function isQuery()
    {
        return 0 === $this->attributes['qr'];
    }

    public function isResponse()
    {
        return 1 === $this->attributes['qr'];
    }

    public function isTruncated()
    {
        return 1 === $this->attributes['tc'];
    }

    public function populateCounts(Message $message)
    {
        $this->attributes['qdCount'] = count($message->questions);
        $this->attributes['anCount'] = count($message->answers);
        $this->attributes['nsCount'] = count($message->authority);
        $this->attributes['arCount'] = count($message->additional);
    }
}
