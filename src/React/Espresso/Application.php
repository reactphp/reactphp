<?php

namespace React\Espresso;

use React\Http\Request;
use React\Http\Response;
use Silex\Application as BaseApplication;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct();

        $this['controllers'] = $this->share(function () {
            return new ControllerCollection();
        });
    }

    public function __invoke(Request $request, Response $response)
    {
        $sfRequest = $this->buildSymfonyRequest($request, $response);
        $this->handle($sfRequest);
    }

    private function buildSymfonyRequest(Request $request, Response $response)
    {
        $sfRequest = SymfonyRequest::create($request->getPath(), $request->getMethod());
        $sfRequest->attributes->set('react.espresso.request', $request);
        $sfRequest->attributes->set('react.espresso.response', $response);

        return $sfRequest;
    }
}
