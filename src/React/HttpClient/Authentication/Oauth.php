<?php

namespace React\HttpClient\Authentication;

use Guzzle\Plugin\Oauth\OauthPlugin;
use Guzzle\Common\Event;
use Guzzle\Http\Message\Request as GuzzleRequest;

/**
 * A class wrapper for the Guzzle oAuth plugin
 */
class Oauth
{
    /**
     * @var Guzzle\Plugin\Oauth\OauthPlugin
     */
    protected $oAuthPlugin;

    public function __construct($config)
    {
        $this->oAuthPlugin = new OauthPlugin($config);
    }

    /**
     * This method runs the actual Guzzle oAuth plugin
     *
     * @param \Guzzle\Http\Message\Request $request
     * @return array
     */
    public function onWritingHeaders(GuzzleRequest $request)
    {
        $event = new Event(array('request' => $request));
        return $this->oAuthPlugin->onRequestBeforeSend($event);
    }
}
