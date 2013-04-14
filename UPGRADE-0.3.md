UPGRADE for 0.3.x
=================

EventLoop
---------

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

HttpClient
----------

* `HttpClient\*ConnectionManager` has been moved to `SocketClient\*Connector`,
  and the `getConnection` method has been renamed to `create`.

  Before:

    $connectionManager->getConnection($host, $port);

  After:

    $connector->create($host, $port);
