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

    public function query($nameserver, Query $query, $callback, $errorback)
    {
        $this->tryQuery($nameserver, $query, $callback, $errorback, $this->retries);
    }

    public function tryQuery($nameserver, Query $query, $callback, $errorback, $retries)
    {
        $that = $this;
        $errorback = function ($error) use ($nameserver, $query, $callback, $errorback, $retries, $that) {
            if (!$error instanceof TimeoutException) {
                $errorback($error);
                return;
            }
            if (0 >= $retries) {
                $error = new \RuntimeException(
                    sprintf("DNS query for %s failed: too many retries", $query->name),
                    0,
                    $error
                );
                $errorback($error);
                return;
            }
            $that->tryQuery($nameserver, $query, $callback, $errorback, $retries-1);
        };
        $this->executor->query($nameserver, $query, $callback, $errorback);
    }
}
