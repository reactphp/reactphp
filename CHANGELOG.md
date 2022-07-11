# Changelog

## 1.3.0 (2022-07-11)

A major new feature release, see [**release announcement**](https://clue.engineering/2022/announcing-reactphp-async).

*   Feature: Add new Async component to core components.
    (#458 by @clue)

*   Feature: Support PHP 8.1 release.
    (#451 by @clue)

*   Improve documentation, update HTTP server example for reactphp/http v1.6.0 release.
    (#449 and #459 by @clue and #457 by @nhedger)

*   Improve test suite, support PHPUnit 9 and update dependencies to avoid skipping tests.
    (#450 and #454 by @clue and #455 by @SimonFrings)

## 1.2.0 (2021-07-11)

A major new feature release, see [**release announcement**](https://clue.engineering/2021/announcing-reactphp-default-loop).

*   Feature: Simplify usage by supporting new [default loop](https://reactphp.org/event-loop/#loop).
    (#445 by @clue)

## 1.1.0 (2020-07-11)

A major new feature release, see [**release announcement**](https://clue.engineering/2020/announcing-reactphp-http).

*   Feature: Add event-driven, streaming HTTP client and server implementation via [`react/http`](https://reactphp.org/http/).
    (#440 by @clue)

*   Update documentation to link to project meta repo and link to our official Gitter chat room.
    (#432 and #433 by @clue)

*   Improve test suite to run tests on PHP 7.4 and add `.gitattributes` to exclude dev files from exports.
    (#434 by @reedy and #439 by @clue)

## 1.0.0 (2019-07-11)

*   First stable LTS release, now following [SemVer](https://semver.org/).
    We'd like to emphasize that this project is production ready and battle-tested.
    We plan to support all long-term support (LTS) releases for at least 24 months,
    so you have a rock-solid foundation to build on top of.

>   ReactPHP consists of a set of individual [components](https://reactphp.org/#core-components).
    This means that instead of installing something like a "ReactPHP framework",
    you actually can pick only the components that you need. As an alternative,
    we also provide this meta package that will install all stable components at
    once. Installing this is only recommended for quick prototyping, as the list
    of stable components may change over time.
    In other words, this meta package does not contain any source code and
    instead only consists of links to all our main components, see also our
    [list of components](https://reactphp.org/#core-components) for more details.

## 0.4.2 (2014-12-11)

**Real Split**: The one where we tag the change where the master repo pulls in all the split components.

New component releases are now tagged and released in their respective
component repository. See also [core components](https://reactphp.org/#core-components)
to learn more about this.

This project continues to be under active development and is anything but dead.
You can check out the combined [changelog for all ReactPHP components](https://reactphp.org/changelog.html).

## 0.4.1 (2014-04-13)

**Hungry Hungry CPU**: CPU starvation bug fixes and other bug fixes.

  * Bug fix: [EventLoop] null timeout in StreamSelectLoop causing 100% CPU usage (@clue)
  * Bug fix: [Socket] Check read buffer for data before shutdown signal and end emit (@ArtyDev)
  * Bug fix: [DNS] Fixed PSR-4 autoload path (@marcj/WyriHaximus)
  * Bug fix: v0.3.4 changes merged for v0.4.1

## 0.4.0 (2014-02-02)

**Fore!**

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

## 0.3.4 (2014-03-30)

  * Bug fix: [Stream] Fixed 100% CPU spike from non-empty write buffer on closed stream
  * Buf fix: [Socket] Reset socket to non-blocking after shutting down (PHP bug)

## 0.3.3 (2013-07-08)

**Connection state bug fixes**

  * Bug fix: [EventLoop] No error on removing non-existent streams (@clue)
  * Bug fix: [EventLoop] Do not silently remove feof listeners in `LibEvLoop`
  * Bug fix: [Stream] Correctly detect closed connections

## 0.3.2 (2013-05-10)

  * Feature: [Dns] Support default port for IPv6 addresses (@clue)
  * Bug fix: [Stream] Make sure CompositeStream is closed properly

## 0.3.1 (2013-04-21)

  * Feature: [Socket] Support binding to IPv6 addresses (@clue)
  * Feature: [SocketClient] Support connecting to IPv6 addresses (@clue)
  * Bug fix: [Stream] Allow any `ReadableStreamInterface` on `BufferedSink::createPromise()`
  * Bug fix: [HttpClient] Correct requirement for socket-client

## 0.3.0 (2013-04-14)

  * BC break: [EventLoop] New timers API (@nrk)
  * BC break: [EventLoop] Remove check on return value from stream callbacks (@nrk)
  * BC break: [HttpClient] Socket connection handling moved to new SocketClient component
  * Feature: [SocketClient] New SocketClient component extracted from HttpClient (@clue)
  * Feature: [Stream] Factory method for BufferedSink

### EventLoop

* The timer callback now receives a `Timer` instance, with the following
  useful methods:

  * `cancel`
  * `isActive`
  * `setData($data)`
  * `getData`

  And some other less common ones. These are prefered over
  `LoopInterface::cancelTimer` and `LoopInterface::isTimerActive`.

* You can no longer return a boolean from a periodic timer callback to abort
  it.

### HttpClient

* `HttpClient\*ConnectionManager` has been moved to `SocketClient\*Connector`,
  and the `getConnection` method has been renamed to `create`.

  Before:

    $connectionManager->getConnection($host, $port);

  After:

    $connector->create($host, $port);

## 0.2.7 (2013-01-05)

  * Bug fix: [EventLoop] Fix libevent timers with PHP 5.3
  * Bug fix: [EventLoop] Fix libevent timer cancellation (@nrk)

## 0.2.6 (2012-12-26)

  * Feature: [Cache] New cache component, used by DNS
  * Bug fix: [Http] Emit end event when Response closes (@beaucollins)
  * Bug fix: [EventLoop] Plug memory issue in libevent timers (@cameronjacobson)
  * Bug fix: [EventLoop] Correctly pause LibEvLoop on stop()

## 0.2.5 (2012-11-26)

  * Feature: [Stream] Make BufferedSink trigger progress events on the promise (@jsor)
  * Feature: [HttpClient] Use a promise-based API internally
  * Bug fix: [HttpClient] Use DNS resolver correctly

## 0.2.4 (2012-11-18)

  * Feature: [Stream] Added ThroughStream, CompositeStream, ReadableStream and WritableStream
  * Feature: [Stream] Added BufferedSink
  * Feature: [Dns] Change to promise-based API (@jsor)

## 0.2.3 (2012-11-14)

  * Feature: LibEvLoop, integration of `php-libev`
  * Bug fix: Forward drain events from HTTP response (@cs278)
  * Dependency: Updated guzzle deps to `3.0.*`

## 0.2.2 (2012-10-28)

  * Major: Dropped Espresso as a core component now available as `react/espresso` only
  * Feature: DNS executor timeout handling (@arnaud-lb)
  * Feature: DNS retry executor (@arnaud-lb)
  * Feature: HTTP client (@arnaud-lb)

## 0.2.1 (2012-10-14)

  * Feature: Support HTTP 1.1 continue
  * Bug fix: Check for EOF in `Buffer::write()`
  * Bug fix: Make `Espresso\Stack` work with invokables (such as `Espresso\Application`)
  * Minor adjustments to DNS parser

## 0.2.0 (2012-09-10)

  * Feature: DNS resolver

## 0.1.1 (2012-07-12)

  * Bug fix: Testing and functional against PHP >= 5.3.3 and <= 5.3.8

## 0.1.0 (2012-07-11)

  * First tagged release
