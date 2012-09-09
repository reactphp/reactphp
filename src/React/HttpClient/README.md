# HttpClient Component

Basic HTTP client.

## Example

```php
<?php

$loop = React\EventLoop\Factory::create();
$client = new React\HttpClient\Client($loop);
$request = $client->request('GET', 'https://github.com/');
$request->on('response', function ($response) {
    $response->on('data', function ($data) {
        // ...
    });
});
$request->end();
```

