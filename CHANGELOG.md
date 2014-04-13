CHANGELOG
=========

### 0.4.1 (2014-04-13)

  * Bug fix: [EventLoop] null timeout in StreamSelectLoop causing 100% CPU usage (@clue)
  * Bug fix: [Socket] Check read buffer for data before shutdown signal and end emit (@ArtyDev)
  * Bug fix: [DNS] Fixed PSR-4 autoload path (@marcj/WyriHaximus)
  * Bug fix: v0.3.4 changes merged for v0.4.1

### 0.3.4 (2014-03-30)

  * Bug fix: [Stream] Fixed 100% CPU spike from non-empty write buffer on closed stream
  * Buf fix: [Socket] Reset socket to non-blocking after shutting down (PHP bug)

### 0.4.0 (2014-02-02)

  * Feature: Added ChildProcess to run async child processes within the event loop (@jmikola)
  * Feature: [EventLoop] Added `EventLoopInterface::nextTick()`, implemented in all event loops (@jmalloc)
  * Feature: [EventLoop] Added `EventLoopInterface::futureTick()`, implemented in all event loops (@jmalloc)
  * Feature: [EventLoop] Added `ExtEventLoop` implementation using pecl/event (@jmalloc)
  * BC break: [HttpClient] Drop unused `Response::getBody()`
  * BC break: Bump minimum PHP version to PHP 5.4, remove 5.3 specific hacks
  * BC break: Remove `$loop` argument from `HttpClient`: `Client`, `Request`, `Response`
  * BC break: Update to React/Promise 2.0
  * BC break: Update to Evenement 2.0
  * BC break: [EventLoop] New method: `EventLoopInterface::nextTick()`
  * BC break: [EventLoop] New method: `EventLoopInterface::futureTick()`
  * Bug fix: [Dns] Properly resolve CNAME aliases
  * Dependency: Autoloading and filesystem structure now PSR-4 instead of PSR-0

### 0.3.3 (2013-07-08)

  * Bug fix: [EventLoop] No error on removing non-existent streams (@clue)
  * Bug fix: [EventLoop] Do not silently remove feof listeners in `LibEvLoop`
  * Bug fix: [Stream] Correctly detect closed connections

### 0.3.2 (2013-05-10)

  * Feature: [Dns] Support default port for IPv6 addresses (@clue)
  * Bug fix: [Stream] Make sure CompositeStream is closed properly

### 0.3.1 (2013-04-21)

  * Feature: [Socket] Support binding to IPv6 addresses (@clue)
  * Feature: [SocketClient] Support connecting to IPv6 addresses (@clue)
  * Bug fix: [Stream] Allow any `ReadableStreamInterface` on `BufferedSink::createPromise()`
  * Bug fix: [HttpClient] Correct requirement for socket-client

### 0.3.0 (2013-04-14)

  * BC break: [EventLoop] New timers API (@nrk)
  * BC break: [EventLoop] Remove check on return value from stream callbacks (@nrk)
  * BC break: [HttpClient] Socket connection handling moved to new SocketClient component
  * Feature: [SocketClient] New SocketClient component extracted from HttpClient (@clue)
  * Feature: [Stream] Factory method for BufferedSink

### 0.2.7 (2013-01-05)

  * Bug fix: [EventLoop] Fix libevent timers with PHP 5.3
  * Bug fix: [EventLoop] Fix libevent timer cancellation (@nrk)

### 0.2.6 (2012-12-26)

  * Feature: [Cache] New cache component, used by DNS
  * Bug fix: [Http] Emit end event when Response closes (@beaucollins)
  * Bug fix: [EventLoop] Plug memory issue in libevent timers (@cameronjacobson)
  * Bug fix: [EventLoop] Correctly pause LibEvLoop on stop()

### 0.2.5 (2012-11-26)

  * Feature: [Stream] Make BufferedSink trigger progress events on the promise (@jsor)
  * Feature: [HttpClient] Use a promise-based API internally
  * Bug fix: [HttpClient] Use DNS resolver correctly

### 0.2.4 (2012-11-18)

  * Feature: [Stream] Added ThroughStream, CompositeStream, ReadableStream and WritableStream
  * Feature: [Stream] Added BufferedSink
  * Feature: [Dns] Change to promise-based API (@jsor)

### 0.2.3 (2012-11-14)

  * Feature: LibEvLoop, integration of `php-libev`
  * Bug fix: Forward drain events from HTTP response (@cs278)
  * Dependency: Updated guzzle deps to `3.0.*`

### 0.2.2 (2012-10-28)

  * Major: Dropped Espresso as a core component now available as `react/espresso` only
  * Feature: DNS executor timeout handling (@arnaud-lb)
  * Feature: DNS retry executor (@arnaud-lb)
  * Feature: HTTP client (@arnaud-lb)

### 0.2.1 (2012-10-14)

  * Feature: Support HTTP 1.1 continue
  * Bug fix: Check for EOF in `Buffer::write()`
  * Bug fix: Make `Espresso\Stack` work with invokables (such as `Espresso\Application`)
  * Minor adjustments to DNS parser

### 0.2.0 (2012-09-10)

  * Feature: DNS resolver

### 0.1.1 (2012-07-12)

  * Bug fix: Testing and functional against PHP >= 5.3.3 and <= 5.3.8

### 0.1.0 (2012-07-11)

  * First tagged release
