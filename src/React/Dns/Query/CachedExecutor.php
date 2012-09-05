<?php

namespace React\Dns\Query;

use React\Dns\Model\Message;
use React\Dns\Model\Record;

class CachedExecutor implements ExecutorInterface
{
    private $executor;
    private $cache;

    public function __construct(ExecutorInterface $executor, RecordCache $cache)
    {
        $this->executor = $executor;
        $this->cache = $cache;
    }

    public function query($nameserver, Query $query, $callback)
    {
        $cachedRecords = $this->cache->lookup($query);
        if (count($cachedRecords)) {
            $cachedResponse = $this->buildResponse($query, $cachedRecords);
            $callback($cachedResponse);
            return;
        }

        $cache = $this->cache;
        $this->executor->query($nameserver, $query, function ($response) use ($cache, $query, $callback) {
            $callback($response);
            $cache->storeResponseMessage($query->currentTime, $response);
        });
    }

    private function buildResponse(Query $query, array $cachedRecords)
    {
        $response = new Message();

        $response->header->set('id', $this->generateId());
        $response->header->set('qr', 1);
        $response->header->set('opcode', Message::OPCODE_QUERY);
        $response->header->set('rd', 1);
        $response->header->set('rcode', Message::RCODE_OK);

        $response->questions[] = new Record($query->name, $query->type, $query->class);

        foreach ($cachedRecords as $record) {
            $response->answers[] = $record;
        }

        $response->prepare();

        return $response;
    }

    protected function generateId()
    {
        return mt_rand(0, 0xffff);
    }
}
