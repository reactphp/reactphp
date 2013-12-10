<?php

namespace React\Dns\Query;

use React\Cache\CacheInterface;
use React\Dns\Model\Message;
use React\Dns\Model\Record;
use React\Promise;

class RecordCache
{
    private $cache;
    private $expiredAt;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function lookup(Query $query)
    {
        $id = $this->serializeQueryToIdentity($query);

        $expiredAt = $this->expiredAt;

        return $this->cache
            ->get($id)
            ->then(function ($value) use ($query, $expiredAt) {
                $recordBag = unserialize($value);

                if (null !== $expiredAt && $expiredAt <= $query->currentTime) {
                    return Promise\reject();
                }

                return $recordBag->all();
            });
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

        $cache = $this->cache;

        $this->cache
            ->get($id)
            ->then(
                function ($value) {
                    return unserialize($value);
                },
                function ($e) {
                    return new RecordBag();
                }
            )
            ->then(function ($recordBag) use ($id, $currentTime, $record, $cache) {
                $recordBag->set($currentTime, $record);
                $cache->set($id, serialize($recordBag));
            });
    }

    public function expire($currentTime)
    {
        $this->expiredAt = $currentTime;
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
