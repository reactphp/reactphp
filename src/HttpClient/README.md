# HttpClient Component

Basic HTTP/1.0 client.

## Basic usage

Requests are prepared using the ``Client#request()`` method. Body can be
sent with ``Request#write()``. ``Request#end()`` finishes sending the request
(or sends it at all if no body was written).

Request implements WritableStreamInterface, so a Stream can be piped to
it. Response implements ReadableStreamInterface.

Interesting events emitted by Request:

* `response`: The response headers were received from the server and successfully
  parsed. The first argument is a Response instance.
* `error`: An error occured.
* `end`: The request is finished. If an error occured, it is passed as first
  argument. Second and third arguments are the Response and the Request.

Interesting events emitted by Response:

* `data`: Passes a chunk of the response body as first argument
* `error`: An error occured.
* `end`: The response has been fully received. If an error
  occured, it is passed as first argument

### Example

```php
<?php

$loop = React\EventLoop\Factory::create();

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

$factory = new React\HttpClient\Factory();
$client = $factory->create($loop, $dnsResolver);

$request = $client->request('GET', 'https://github.com/');
$request->on('response', function ($response) {
    $response->on('data', function ($data) {
        // ...
    });
});
$request->end();
```

## TODO

* gzip content encoding
* chunked transfer encoding
* keep-alive connections
* following redirections
