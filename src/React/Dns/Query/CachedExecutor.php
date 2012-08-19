<?php

namespace React\Dns\Query;

use React\Dns\Model\Message;

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
        $cachedRecord = $this->cache->lookup($query);
        if (null !== $cachedRecord) {
            $callback($cachedRecord);
            return;
        }

        $cache = $this->cache;
        $this->executor->query($nameserver, $query, function ($response) use ($cache, $query, $callback) {
            $callback($response);
            $cache->storeResponseMessage($response);
        });
    }
}
