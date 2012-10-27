<?php

namespace React\Dns\Query;

use React\Dns\Model\Message;
use React\Dns\Model\Record;
use React\Promise\Util;

class CachedExecutor implements ExecutorInterface
{
    private $executor;
    private $cache;

    public function __construct(ExecutorInterface $executor, RecordCache $cache)
    {
        $this->executor = $executor;
        $this->cache = $cache;
    }

    public function query($nameserver, Query $query)
    {
        $cachedRecords = $this->cache->lookup($query);

        if (count($cachedRecords)) {
            $cachedResponse = $this->buildResponse($query, $cachedRecords);

            return Util::resolve($cachedResponse);
        }

        $cache = $this->cache;

        return $this->executor
            ->query($nameserver, $query)
            ->then(function ($response) use ($cache, $query) {
                $cache->storeResponseMessage($query->currentTime, $response);
                return $response;
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
