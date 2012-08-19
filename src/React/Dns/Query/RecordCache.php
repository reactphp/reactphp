<?php

namespace React\Dns\Query;

use React\Dns\Model\Message;
use React\Dns\Model\Record;

class RecordCache
{
    private $records = array();

    public function lookup(Query $query)
    {
        $id = $this->serializeQuery($query);
        $record = isset($this->records[$id]) ? $this->records[$id] : null;

        return $record;
    }

    public function storeResponseMessage(Message $message)
    {
        foreach ($message->answers as $record) {
            $this->storeRecord($record);
        }
    }

    public function storeRecord(Record $record)
    {
        $id = $this->serializeRecord($record);
        $this->records[$id] = $record;
    }

    public function serializeQuery(Query $query)
    {
        return sprintf('%s:%s:%s', $query->name, $query->type, $query->class);
    }

    public function serializeRecord(Record $record)
    {
        return sprintf('%s:%s:%s', $record->name, $record->type, $record->class);
    }
}
