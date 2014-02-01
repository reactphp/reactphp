<?php

namespace React\Dns\Query;

class Query
{
    public $name;
    public $type;
    public $class;
    public $currentTime;

    public function __construct($name, $type, $class, $currentTime)
    {
        $this->name = $name;
        $this->type = $type;
        $this->class = $class;
        $this->currentTime = $currentTime;
    }
}
