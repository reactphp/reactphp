<?php

namespace React\Dns\Resolver;

use React\Dns\Query\ExecutorInterface;
use React\Dns\Query\Query;
use React\Dns\RecordNotFoundException;
use React\Dns\Model\Message;

class Resolver
{
    private $nameserver;
    private $executor;

    public function __construct($nameserver, ExecutorInterface $executor)
    {
        $this->nameserver = $nameserver;
        $this->executor = $executor;
    }

    public function resolve($domain)
    {
        $that = $this;

        $query = new Query($domain, Message::TYPE_A, Message::CLASS_IN, time());

        return $this->executor
            ->query($this->nameserver, $query)
            ->then(function (Message $response) use ($that) {
                return $that->extractAddress($response, Message::TYPE_A);
            });
    }

    public function extractAddress(Message $response, $type)
    {
        $answer = $this->pickRandomAnswerOfType($response, $type);
        $address = $answer->data;
        return $address;
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
