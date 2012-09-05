<?php

namespace React\Dns\Query;

use React\Dns\Model\Message;
use React\Dns\Model\Record;

class RecordCache
{
    private $records = array();

    public function lookup(Query $query)
    {
        $id = $this->serializeQueryToIdentity($query);
        $records = isset($this->records[$id]) ? $this->records[$id]->all() : array();

        return $records;
    }

    public function storeResponseMessage($currentTime, Message $message)
    {
        foreach ($message->answers as $record) {
            $this->storeRecord($currentTime, $record);
        }
    }

    public function storeRecord($currentTime, Record $record)
    {
        $id = $this->serializeRecordToIdentity($record);
        $this->records[$id] = isset($this->records[$id]) ? $this->records[$id] : new RecordBag();
        $this->records[$id]->set($currentTime, $record);
    }

    public function expire($currentTime)
    {
        foreach ($this->records as $recordBag) {
            $recordBag->expire($currentTime);
        }
    }

    public function serializeQueryToIdentity(Query $query)
    {
        return sprintf('%s:%s:%s', $query->name, $query->type, $query->class);
    }

    public function serializeRecordToIdentity(Record $record)
    {
        return sprintf('%s:%s:%s', $record->name, $record->type, $record->class);
    }
}
