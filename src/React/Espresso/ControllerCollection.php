<?php

namespace React\Espresso;

use React\Http\Request;
use React\Http\Response;
use Silex\ControllerCollection as BaseControllerCollection;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\StreamedResponse as SymfonyStreamedResponse;

class ControllerCollection extends BaseControllerCollection
{
    public function match($pattern, $to)
    {
        $wrapped = $this->wrapController($to);
        return parent::match($pattern, $wrapped);
    }

    private function wrapController($controller)
    {
        return function (SymfonyRequest $sfRequest) use ($controller) {
            $request = $sfRequest->attributes->get('react.espresso.request');
            $response = $sfRequest->attributes->get('react.espresso.response');

            call_user_func($controller, $request, $response);

            return new SymfonyStreamedResponse();
        };
    }
}
