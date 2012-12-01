<?php

namespace React\Dns\Query;

use React\Dns\Model\Message;
use React\Dns\Model\Record;

class RecordBag
{
    private $records = array();

    public function set($currentTime, Record $record)
    {
        $this->records[$record->data] = array($currentTime + $record->ttl, $record);
    }

    public function all()
    {
        return array_values(array_map(
            function ($value) {
                list($expiresAt, $record) = $value;
                return $record;
            },
            $this->records
        ));
    }
}
