<?php

namespace React\Dns;

class Message
{
    const TYPE_A = 1;
    const TYPE_NS = 2;
    const TYPE_CNAME = 5;
    const TYPE_SOA = 6;
    const TYPE_PTR = 12;
    const TYPE_MX = 15;
    const TYPE_TXT = 16;

    const CLASS_IN = 1;

    const OPCODE_QUERY = 0;
    const OPCODE_IQUERY = 1; // inverse query
    const OPCODE_STATUS = 2;

    public $data = '';

    public $header = array();
    public $question = array();
    public $answer = array();
    public $authority = array();
    public $additional = array();

    public function getId()
    {
        return $this->header['id'];
    }

    public function isQuery()
    {
        return 0 === $this->header['qr'];
    }

    public function isResponse()
    {
        return 1 === $this->header['qr'];
    }
}
