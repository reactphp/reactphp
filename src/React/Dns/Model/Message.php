<?php

namespace React\Dns\Model;

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

    const RCODE_OK = 0;
    const RCODE_FORMAT_ERROR = 1;
    const RCODE_SERVER_FAILURE = 2;
    const RCODE_NAME_ERROR = 3;
    const RCODE_NOT_IMPLEMENTED = 4;
    const RCODE_REFUSED = 5;

    public $data = '';

    public $headers;
    public $questions = array();
    public $answers = array();
    public $authority = array();
    public $additional = array();

    public function __construct()
    {
        $this->headers = new HeaderBag();
    }

    public function prepare()
    {
        $this->headers->populateCounts($this);
    }
}
