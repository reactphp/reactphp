UPGRADE for 0.3.x
=================

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
