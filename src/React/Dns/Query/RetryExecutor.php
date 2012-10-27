<?php

namespace React\Dns\Query;

class RetryExecutor implements ExecutorInterface
{
    private $executor;
    private $cache;
    private $retries;

    public function __construct(ExecutorInterface $executor, $retries = 2)
    {
        $this->executor = $executor;
        $this->retries = $retries;
    }

    public function query($nameserver, Query $query)
    {
        return $this->tryQuery($nameserver, $query, $this->retries);
    }

    public function tryQuery($nameserver, Query $query, $retries)
    {
        $that = $this;
        $errorback = function ($error) use ($nameserver, $query, $retries, $that) {
            if (!$error instanceof TimeoutException) {
                return $error;
            }
            if (0 >= $retries) {
                $error = new \RuntimeException(
                    sprintf("DNS query for %s failed: too many retries", $query->name),
                    0,
                    $error
                );
                return $error;
            }
            return $that->tryQuery($nameserver, $query, $retries-1);
        };

        return $this->executor->query($nameserver, $query, $errorback)
                   ->then(null, $errorback);
    }
}
