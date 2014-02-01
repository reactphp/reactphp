<?php

namespace React\Dns\Query;

use React\Promise\Deferred;

class RetryExecutor implements ExecutorInterface
{
    private $executor;
    private $retries;

    public function __construct(ExecutorInterface $executor, $retries = 2)
    {
        $this->executor = $executor;
        $this->retries = $retries;
    }

    public function query($nameserver, Query $query)
    {
        $deferred = new Deferred();

        $this->tryQuery($nameserver, $query, $this->retries, $deferred);

        return $deferred->promise();
    }

    public function tryQuery($nameserver, Query $query, $retries, $deferred)
    {
        $errorback = function ($error) use ($nameserver, $query, $retries, $deferred) {
            if (!$error instanceof TimeoutException) {
                $deferred->reject($error);
                return;
            }
            if (0 >= $retries) {
                $error = new \RuntimeException(
                    sprintf("DNS query for %s failed: too many retries", $query->name),
                    0,
                    $error
                );
                $deferred->reject($error);
                return;
            }
            $this->tryQuery($nameserver, $query, $retries-1, $deferred);
        };

        $this->executor
            ->query($nameserver, $query)
            ->then(array($deferred, 'resolve'), $errorback);
    }
}
