# SocketClient Component

Async Connector to open TCP/IP and SSL/TLS based connections.

## Introduction

Think of this library as an async version of
[`fsockopen()`](http://www.php.net/function.fsockopen) or
[`stream_socket_client()`](http://php.net/function.stream-socket-client).

Before you can actually transmit and receive data to/from a remote server, you
have to establish a connection to the remote end. Establishing this connection
through the internet/network takes some time as it requires several steps in
order to complete:

1. Resolve remote target hostname via DNS (+cache)
2. Complete TCP handshake (2 roundtrips) with remote target IP:port
3. Optionally enable SSL/TLS on the new resulting connection

## Usage

In order to use this project, you'll need the following react boilerplate code
to initialize the main loop and select your DNS server if you have not already
set it up anyway.

```php
$loop = React\EventLoop\Factory::create();

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);
```

### Async TCP/IP connections

The `React\SocketClient\Connector` provides a single promise-based
`create($host, $ip)` method which resolves as soon as the connection
succeeds or fails.

```php
$connector = new React\SocketClient\Connector($loop, $dns);

$connector->create('www.google.com', 80)->then(function (React\Stream\Stream $stream) {
    $stream->write('...');
    $stream->close();
});
```

### Async SSL/TLS connections

The `SecureConnector` class decorates a given `Connector` instance by enabling
SSL/TLS encryption as soon as the raw TCP/IP connection succeeds. It provides
the same promise- based `create($host, $ip)` method which resolves with
a `Stream` instance that can be used just like any non-encrypted stream.

```php
$secureConnector = new React\SocketClient\SecureConnector($connector, $loop);

$secureConnector->create('www.google.com', 443)->then(function (React\Stream\Stream $stream) {
    $stream->write("GET / HTTP/1.0\r\nHost: www.google.com\r\n\r\n");
    ...
});
```
