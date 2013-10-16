# Child Process Component

Library for executing child processes.

## Introduction

This library integrates the
[Program Execution](http://php.net/manual/en/book.exec.php) extension in PHP
with React's event loop.

Child processes launched within the event loop may be signaled and will emit an
`exit` event upon termination. Additionally, process I/O streams (i.e. stdin,
stdout, stderr) are registered with the loop.

## Processes

### EventEmitter Events

* `exit`: Emitted whenever the process is no longer running. Event listeners
  will receive the exit code and termination signal as two arguments.

### Methods

* `start()`: Launches the process and registers its IO streams with the event
  loop. The stdin stream will be left in a paused state.
* `terminate()`: Send the process a signal (SIGTERM by default).

There are additional public methods on the Process class, which may be used to
access fields otherwise available through `proc_get_status()`.

### Stream Properties

Once a process is started, its I/O streams will be constructed as instances of
`React\Stream\Stream`. Before `start()` is called, these properties are `null`.
Once a process terminates, the streams will become closed but not unset.

* `$stdin`
* `$stdout`
* `$stderr`

## Usage

    $loop = React\EventLoop\Factory::create();

    $process = new React\ChildProcess\Process('echo foo');

    $process->on('exit', function($exitCode, $termSignal) {
        // ...
    });

    $loop->addTimer(0.001, function($timer) use ($process) {
        $process->start($timer->getLoop());

        $process->stdout->on('data', function($output) {
            // ...
        });
    });

    $loop->run();

### Prepending Commands with `exec`

Symfony pull request [#5759](https://github.com/symfony/symfony/issues/5759)
documents a caveat with the
[Program Execution](http://php.net/manual/en/book.exec.php) extension. PHP will
launch processes via `sh`, which obfuscates the underlying process' PID and
complicates signaling (our process becomes a child of `sh`). As a work-around,
prepend the command string with `exec`, which will cause the `sh` process to be
replaced by our process.

### Sigchild Compatibility

When PHP has been compiled with the `--enabled-sigchild` option, a child
process' exit code cannot be reliably determined via `proc_close()` or
`proc_get_status()`. Instead, we execute the child process with a fourth pipe
and use that to retrieve its exit code.

This behavior is used by default and only when necessary. It may be manually
disabled by calling `setEnhanceSigchildCompatibility(false)` on the Process
before it is started, in which case the `exit` event may receive `null` instead
of the actual exit code.

**Note:** This functionality was taken from Symfony's
[Process](https://github.com/symfony/process) compoment.

### Command Chaining

Command chaning with `&&` or `;`, while possible with `proc_open()`, should not
be used with this component. There is currently no way to discern when each
process in a chain ends, which would complicate working with I/O streams. As an
alternative, considering launching one process at a time and listening on its
`exit` event to conditionally start the next process in the chain. This will
give you an opportunity to configure the subsequent process' I/O streams.
