<?php

namespace React\Dns\Resolver;

use React\Dns\Query\ExecutorInterface;
use React\Dns\Query\Query;
use React\Dns\BadServerException;
use React\Dns\RecordNotFoundException;
use React\Dns\Model\Message;
use React\Dns\Protocol\Parser;
use React\Dns\Protocol\BinaryDumper;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;

class Resolver
{
    private $nameserver;
    private $executor;

    public function __construct($nameserver, ExecutorInterface $executor)
    {
        $this->nameserver = $nameserver;
        $this->executor = $executor;
    }

    public function resolve($domain, $callback, $errback = null)
    {
        $that = $this;

        $query = new Query($domain, Message::TYPE_A, Message::CLASS_IN, time());

        $this->executor->query($this->nameserver, $query, function (Message $response) use ($that, $callback, $errback) {
            try {
                $that->extractAddress($response, Message::TYPE_A, $callback);
            } catch (RecordNotFoundException $e) {
                if (!$errback) {
                    throw $e;
                }

                $errback($e);
            }
        });
    }

    public function extractAddress(Message $response, $type, $callback)
    {
        $answer = $this->pickRandomAnswerOfType($response, $type);
        $address = $answer->data;
        $callback($address);
    }

    public function pickRandomAnswerOfType(Message $response, $type)
    {
        // TODO: filter by name to make sure domain matches
        // TODO: resolve CNAME aliases

        $filteredAnswers = array_filter($response->answers, function ($answer) use ($type) {
            return $type === $answer->type;
        });

        if (0 === count($filteredAnswers)) {
            $message = sprintf('DNS Request did not return valid answer. Received answers: %s', json_encode($response->answers));
            throw new RecordNotFoundException($message);
        }

        $answer = $filteredAnswers[array_rand($filteredAnswers)];

        return $answer;
    }
}
