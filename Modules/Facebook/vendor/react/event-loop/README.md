# EventLoop Component

[![Build Status](https://travis-ci.org/reactphp/event-loop.svg?branch=master)](https://travis-ci.org/reactphp/event-loop)

[ReactPHP](https://reactphp.org/)'s core reactor event loop that libraries can use for evented I/O.

In order for async based libraries to be interoperable, they need to use the
same event loop. This component provides a common `LoopInterface` that any
library can target. This allows them to be used in the same loop, with one
single [`run()`](#run) call that is controlled by the user.

**Table of Contents**

* [Quickstart example](#quickstart-example)
* [Usage](#usage)
  * [Factory](#factory)
    * [create()](#create)
  * [Loop implementations](#loop-implementations)
    * [StreamSelectLoop](#streamselectloop)
    * [ExtEventLoop](#exteventloop)
    * [ExtLibeventLoop](#extlibeventloop)
    * [ExtLibevLoop](#extlibevloop)
    * [ExtEvLoop](#extevloop)
    * [ExtUvLoop](#extuvloop)
  * [LoopInterface](#loopinterface)
    * [run()](#run)
    * [stop()](#stop)
    * [addTimer()](#addtimer)
    * [addPeriodicTimer()](#addperiodictimer)
    * [cancelTimer()](#canceltimer)
    * [futureTick()](#futuretick)
    * [addSignal()](#addsignal)
    * [removeSignal()](#removesignal)
    * [addReadStream()](#addreadstream)
    * [addWriteStream()](#addwritestream)
    * [removeReadStream()](#removereadstream)
    * [removeWriteStream()](#removewritestream)
* [Install](#install)
* [Tests](#tests)
* [License](#license)
* [More](#more)

## Quickstart example

Here is an async HTTP server built with just the event loop.

```php
$loop = React\EventLoop\Factory::create();

$server = stream_socket_server('tcp://127.0.0.1:8080');
stream_set_blocking($server, false);

$loop->addReadStream($server, function ($server) use ($loop) {
    $conn = stream_socket_accept($server);
    $data = "HTTP/1.1 200 OK\r\nContent-Length: 3\r\n\r\nHi\n";
    $loop->addWriteStream($conn, function ($conn) use (&$data, $loop) {
        $written = fwrite($conn, $data);
        if ($written === strlen($data)) {
            fclose($conn);
            $loop->removeWriteStream($conn);
        } else {
            $data = substr($data, $written);
        }
    });
});

$loop->addPeriodicTimer(5, function () {
    $memory = memory_get_usage() / 1024;
    $formatted = number_format($memory, 3).'K';
    echo "Current memory usage: {$formatted}\n";
});

$loop->run();
```

See also the [examples](examples).

## Usage

Typical applications use a single event loop which is created at the beginning
and run at the end of the program.

```php
// [1]
$loop = React\EventLoop\Factory::create();

// [2]
$loop->addPeriodicTimer(1, function () {
    echo "Tick\n";
});

$stream = new React\Stream\ReadableResourceStream(
    fopen('file.txt', 'r'),
    $loop
);

// [3]
$loop->run();
```

1. The loop instance is created at the beginning of the program. A convenience
   factory [`React\EventLoop\Factory::create()`](#create) is provided by this library which
   picks the best available [loop implementation](#loop-implementations).
2. The loop instance is used directly or passed to library and application code.
   In this example, a periodic timer is registered with the event loop which
   simply outputs `Tick` every second and a
   [readable stream](https://github.com/reactphp/stream#readableresourcestream)
   is created by using ReactPHP's
   [stream component](https://github.com/reactphp/stream) for demonstration
   purposes.
3. The loop is run with a single [`$loop->run()`](#run) call at the end of the program.

### Factory

The `Factory` class exists as a convenient way to pick the best available
[event loop implementation](#loop-implementations).

#### create()

The `create(): LoopInterface` method can be used to create a new event loop
instance:

```php
$loop = React\EventLoop\Factory::create();
```

This method always returns an instance implementing [`LoopInterface`](#loopinterface),
the actual [event loop implementation](#loop-implementations) is an implementation detail.

This method should usually only be called once at the beginning of the program.

### Loop implementations

In addition to the [`LoopInterface`](#loopinterface), there are a number of
event loop implementations provided.

All of the event loops support these features:

* File descriptor polling
* One-off timers
* Periodic timers
* Deferred execution on future loop tick

For most consumers of this package, the underlying event loop implementation is
an implementation detail.
You should use the [`Factory`](#factory) to automatically create a new instance.

Advanced! If you explicitly need a certain event loop implementation, you can
manually instantiate one of the following classes.
Note that you may have to install the required PHP extensions for the respective
event loop implementation first or they will throw a `BadMethodCallException` on creation.

#### StreamSelectLoop

A `stream_select()` based event loop.

This uses the [`stream_select()`](https://www.php.net/manual/en/function.stream-select.php)
function and is the only implementation which works out of the box with PHP.

This event loop works out of the box on PHP 5.3 through PHP 7+ and HHVM.
This means that no installation is required and this library works on all
platforms and supported PHP versions.
Accordingly, the [`Factory`](#factory) will use this event loop by default if
you do not install any of the event loop extensions listed below.

Under the hood, it does a simple `select` system call.
This system call is limited to the maximum file descriptor number of
`FD_SETSIZE` (platform dependent, commonly 1024) and scales with `O(m)`
(`m` being the maximum file descriptor number passed).
This means that you may run into issues when handling thousands of streams
concurrently and you may want to look into using one of the alternative
event loop implementations listed below in this case.
If your use case is among the many common use cases that involve handling only
dozens or a few hundred streams at once, then this event loop implementation
performs really well.

If you want to use signal handling (see also [`addSignal()`](#addsignal) below),
this event loop implementation requires `ext-pcntl`.
This extension is only available for Unix-like platforms and does not support
Windows.
It is commonly installed as part of many PHP distributions.
If this extension is missing (or you're running on Windows), signal handling is
not supported and throws a `BadMethodCallException` instead.

This event loop is known to rely on wall-clock time to schedule future timers
when using any version before PHP 7.3, because a monotonic time source is
only available as of PHP 7.3 (`hrtime()`).
While this does not affect many common use cases, this is an important
distinction for programs that rely on a high time precision or on systems
that are subject to discontinuous time adjustments (time jumps).
This means that if you schedule a timer to trigger in 30s on PHP < 7.3 and
then adjust your system time forward by 20s, the timer may trigger in 10s.
See also [`addTimer()`](#addtimer) for more details.

#### ExtEventLoop

An `ext-event` based event loop.

This uses the [`event` PECL extension](https://pecl.php.net/package/event).
It supports the same backends as libevent.

This loop is known to work with PHP 5.4 through PHP 7+.

#### ExtEvLoop

An `ext-ev` based event loop.

This loop uses the [`ev` PECL extension](https://pecl.php.net/package/ev), that
provides an interface to `libev` library.

This loop is known to work with PHP 5.4 through PHP 7+.

#### ExtUvLoop

An `ext-uv` based event loop.

This loop uses the [`uv` PECL extension](https://pecl.php.net/package/uv), that
provides an interface to `libuv` library.

This loop is known to work with PHP 7+.

#### ExtLibeventLoop

An `ext-libevent` based event loop.

This uses the [`libevent` PECL extension](https://pecl.php.net/package/libevent).
`libevent` itself supports a number of system-specific backends (epoll, kqueue).

This event loop does only work with PHP 5.
An [unofficial update](https://github.com/php/pecl-event-libevent/pull/2) for
PHP 7 does exist, but it is known to cause regular crashes due to `SEGFAULT`s.
To reiterate: Using this event loop on PHP 7 is not recommended.
Accordingly, the [`Factory`](#factory) will not try to use this event loop on
PHP 7.

This event loop is known to trigger a readable listener only if
the stream *becomes* readable (edge-triggered) and may not trigger if the
stream has already been readable from the beginning.
This also implies that a stream may not be recognized as readable when data
is still left in PHP's internal stream buffers.
As such, it's recommended to use `stream_set_read_buffer($stream, 0);`
to disable PHP's internal read buffer in this case.
See also [`addReadStream()`](#addreadstream) for more details.

#### ExtLibevLoop

An `ext-libev` based event loop.

This uses an [unofficial `libev` extension](https://github.com/m4rw3r/php-libev).
It supports the same backends as libevent.

This loop does only work with PHP 5.
An update for PHP 7 is [unlikely](https://github.com/m4rw3r/php-libev/issues/8)
to happen any time soon.

### LoopInterface

#### run()

The `run(): void` method can be used to
run the event loop until there are no more tasks to perform.

For many applications, this method is the only directly visible
invocation on the event loop.
As a rule of thumb, it is usally recommended to attach everything to the
same loop instance and then run the loop once at the bottom end of the
application.

```php
$loop->run();
```

This method will keep the loop running until there are no more tasks
to perform. In other words: This method will block until the last
timer, stream and/or signal has been removed.

Likewise, it is imperative to ensure the application actually invokes
this method once. Adding listeners to the loop and missing to actually
run it will result in the application exiting without actually waiting
for any of the attached listeners.

This method MUST NOT be called while the loop is already running.
This method MAY be called more than once after it has explicity been
[`stop()`ped](#stop) or after it automatically stopped because it
previously did no longer have anything to do.

#### stop()

The `stop(): void` method can be used to
instruct a running event loop to stop.

This method is considered advanced usage and should be used with care.
As a rule of thumb, it is usually recommended to let the loop stop
only automatically when it no longer has anything to do.

This method can be used to explicitly instruct the event loop to stop:

```php
$loop->addTimer(3.0, function () use ($loop) {
    $loop->stop();
});
```

Calling this method on a loop instance that is not currently running or
on a loop instance that has already been stopped has no effect.

#### addTimer()

The `addTimer(float $interval, callable $callback): TimerInterface` method can be used to
enqueue a callback to be invoked once after the given interval.

The timer callback function MUST be able to accept a single parameter,
the timer instance as also returned by this method or you MAY use a
function which has no parameters at all.

The timer callback function MUST NOT throw an `Exception`.
The return value of the timer callback function will be ignored and has
no effect, so for performance reasons you're recommended to not return
any excessive data structures.

Unlike [`addPeriodicTimer()`](#addperiodictimer), this method will ensure
the callback will be invoked only once after the given interval.
You can invoke [`cancelTimer`](#canceltimer) to cancel a pending timer.

```php
$loop->addTimer(0.8, function () {
    echo 'world!' . PHP_EOL;
});

$loop->addTimer(0.3, function () {
    echo 'hello ';
});
```

See also [example #1](examples).

If you want to access any variables within your callback function, you
can bind arbitrary data to a callback closure like this:

```php
function hello($name, LoopInterface $loop)
{
    $loop->addTimer(1.0, function () use ($name) {
        echo "hello $name\n";
    });
}

hello('Tester', $loop);
```

This interface does not enforce any particular timer resolution, so
special care may have to be taken if you rely on very high precision with
millisecond accuracy or below. Event loop implementations SHOULD work on
a best effort basis and SHOULD provide at least millisecond accuracy
unless otherwise noted. Many existing event loop implementations are
known to provide microsecond accuracy, but it's generally not recommended
to rely on this high precision.

Similarly, the execution order of timers scheduled to execute at the
same time (within its possible accuracy) is not guaranteed.

This interface suggests that event loop implementations SHOULD use a
monotonic time source if available. Given that a monotonic time source is
only available as of PHP 7.3 by default, event loop implementations MAY
fall back to using wall-clock time.
While this does not affect many common use cases, this is an important
distinction for programs that rely on a high time precision or on systems
that are subject to discontinuous time adjustments (time jumps).
This means that if you schedule a timer to trigger in 30s and then adjust
your system time forward by 20s, the timer SHOULD still trigger in 30s.
See also [event loop implementations](#loop-implementations) for more details.

#### addPeriodicTimer()

The `addPeriodicTimer(float $interval, callable $callback): TimerInterface` method can be used to
enqueue a callback to be invoked repeatedly after the given interval.

The timer callback function MUST be able to accept a single parameter,
the timer instance as also returned by this method or you MAY use a
function which has no parameters at all.

The timer callback function MUST NOT throw an `Exception`.
The return value of the timer callback function will be ignored and has
no effect, so for performance reasons you're recommended to not return
any excessive data structures.

Unlike [`addTimer()`](#addtimer), this method will ensure the the
callback will be invoked infinitely after the given interval or until you
invoke [`cancelTimer`](#canceltimer).

```php
$timer = $loop->addPeriodicTimer(0.1, function () {
    echo 'tick!' . PHP_EOL;
});

$loop->addTimer(1.0, function () use ($loop, $timer) {
    $loop->cancelTimer($timer);
    echo 'Done' . PHP_EOL;
});
```

See also [example #2](examples).

If you want to limit the number of executions, you can bind
arbitrary data to a callback closure like this:

```php
function hello($name, LoopInterface $loop)
{
    $n = 3;
    $loop->addPeriodicTimer(1.0, function ($timer) use ($name, $loop, &$n) {
        if ($n > 0) {
            --$n;
            echo "hello $name\n";
        } else {
            $loop->cancelTimer($timer);
        }
    });
}

hello('Tester', $loop);
```

This interface does not enforce any particular timer resolution, so
special care may have to be taken if you rely on very high precision with
millisecond accuracy or below. Event loop implementations SHOULD work on
a best effort basis and SHOULD provide at least millisecond accuracy
unless otherwise noted. Many existing event loop implementations are
known to provide microsecond accuracy, but it's generally not recommended
to rely on this high precision.

Similarly, the execution order of timers scheduled to execute at the
same time (within its possible accuracy) is not guaranteed.

This interface suggests that event loop implementations SHOULD use a
monotonic time source if available. Given that a monotonic time source is
only available as of PHP 7.3 by default, event loop implementations MAY
fall back to using wall-clock time.
While this does not affect many common use cases, this is an important
distinction for programs that rely on a high time precision or on systems
that are subject to discontinuous time adjustments (time jumps).
This means that if you schedule a timer to trigger in 30s and then adjust
your system time forward by 20s, the timer SHOULD still trigger in 30s.
See also [event loop implementations](#loop-implementations) for more details.

Additionally, periodic timers may be subject to timer drift due to
re-scheduling after each invocation. As such, it's generally not
recommended to rely on this for high precision intervals with millisecond
accuracy or below.

#### cancelTimer()

The `cancelTimer(TimerInterface $timer): void` method can be used to
cancel a pending timer.

See also [`addPeriodicTimer()`](#addperiodictimer) and [example #2](examples).

Calling this method on a timer instance that has not been added to this
loop instance or on a timer that has already been cancelled has no effect.

#### futureTick()

The `futureTick(callable $listener): void` method can be used to
schedule a callback to be invoked on a future tick of the event loop.

This works very much similar to timers with an interval of zero seconds,
but does not require the overhead of scheduling a timer queue.

The tick callback function MUST be able to accept zero parameters.

The tick callback function MUST NOT throw an `Exception`.
The return value of the tick callback function will be ignored and has
no effect, so for performance reasons you're recommended to not return
any excessive data structures.

If you want to access any variables within your callback function, you
can bind arbitrary data to a callback closure like this:

```php
function hello($name, LoopInterface $loop)
{
    $loop->futureTick(function () use ($name) {
        echo "hello $name\n";
    });
}

hello('Tester', $loop);
```

Unlike timers, tick callbacks are guaranteed to be executed in the order
they are enqueued.
Also, once a callback is enqueued, there's no way to cancel this operation.

This is often used to break down bigger tasks into smaller steps (a form
of cooperative multitasking).

```php
$loop->futureTick(function () {
    echo 'b';
});
$loop->futureTick(function () {
    echo 'c';
});
echo 'a';
```

See also [example #3](examples).

#### addSignal()

The `addSignal(int $signal, callable $listener): void` method can be used to
register a listener to be notified when a signal has been caught by this process.

This is useful to catch user interrupt signals or shutdown signals from
tools like `supervisor` or `systemd`.

The listener callback function MUST be able to accept a single parameter,
the signal added by this method or you MAY use a function which
has no parameters at all.

The listener callback function MUST NOT throw an `Exception`.
The return value of the listener callback function will be ignored and has
no effect, so for performance reasons you're recommended to not return
any excessive data structures.

```php
$loop->addSignal(SIGINT, function (int $signal) {
    echo 'Caught user interrupt signal' . PHP_EOL;
});
```

See also [example #4](examples).

Signaling is only available on Unix-like platform, Windows isn't
supported due to operating system limitations.
This method may throw a `BadMethodCallException` if signals aren't
supported on this platform, for example when required extensions are
missing.

**Note: A listener can only be added once to the same signal, any
attempts to add it more then once will be ignored.**

#### removeSignal()

The `removeSignal(int $signal, callable $listener): void` method can be used to
remove a previously added signal listener.

```php
$loop->removeSignal(SIGINT, $listener);
```

Any attempts to remove listeners that aren't registered will be ignored.

#### addReadStream()

> Advanced! Note that this low-level API is considered advanced usage.
  Most use cases should probably use the higher-level
  [readable Stream API](https://github.com/reactphp/stream#readablestreaminterface)
  instead.

The `addReadStream(resource $stream, callable $callback): void` method can be used to
register a listener to be notified when a stream is ready to read.

The first parameter MUST be a valid stream resource that supports
checking whether it is ready to read by this loop implementation.
A single stream resource MUST NOT be added more than once.
Instead, either call [`removeReadStream()`](#removereadstream) first or
react to this event with a single listener and then dispatch from this
listener. This method MAY throw an `Exception` if the given resource type
is not supported by this loop implementation.

The listener callback function MUST be able to accept a single parameter,
the stream resource added by this method or you MAY use a function which
has no parameters at all.

The listener callback function MUST NOT throw an `Exception`.
The return value of the listener callback function will be ignored and has
no effect, so for performance reasons you're recommended to not return
any excessive data structures.

If you want to access any variables within your callback function, you
can bind arbitrary data to a callback closure like this:

```php
$loop->addReadStream($stream, function ($stream) use ($name) {
    echo $name . ' said: ' . fread($stream);
});
```

See also [example #11](examples).

You can invoke [`removeReadStream()`](#removereadstream) to remove the
read event listener for this stream.

The execution order of listeners when multiple streams become ready at
the same time is not guaranteed.

Some event loop implementations are known to only trigger the listener if
the stream *becomes* readable (edge-triggered) and may not trigger if the
stream has already been readable from the beginning.
This also implies that a stream may not be recognized as readable when data
is still left in PHP's internal stream buffers.
As such, it's recommended to use `stream_set_read_buffer($stream, 0);`
to disable PHP's internal read buffer in this case.

#### addWriteStream()

> Advanced! Note that this low-level API is considered advanced usage.
  Most use cases should probably use the higher-level
  [writable Stream API](https://github.com/reactphp/stream#writablestreaminterface)
  instead.

The `addWriteStream(resource $stream, callable $callback): void` method can be used to
register a listener to be notified when a stream is ready to write.

The first parameter MUST be a valid stream resource that supports
checking whether it is ready to write by this loop implementation.
A single stream resource MUST NOT be added more than once.
Instead, either call [`removeWriteStream()`](#removewritestream) first or
react to this event with a single listener and then dispatch from this
listener. This method MAY throw an `Exception` if the given resource type
is not supported by this loop implementation.

The listener callback function MUST be able to accept a single parameter,
the stream resource added by this method or you MAY use a function which
has no parameters at all.

The listener callback function MUST NOT throw an `Exception`.
The return value of the listener callback function will be ignored and has
no effect, so for performance reasons you're recommended to not return
any excessive data structures.

If you want to access any variables within your callback function, you
can bind arbitrary data to a callback closure like this:

```php
$loop->addWriteStream($stream, function ($stream) use ($name) {
    fwrite($stream, 'Hello ' . $name);
});
```

See also [example #12](examples).

You can invoke [`removeWriteStream()`](#removewritestream) to remove the
write event listener for this stream.

The execution order of listeners when multiple streams become ready at
the same time is not guaranteed.

#### removeReadStream()

The `removeReadStream(resource $stream): void` method can be used to
remove the read event listener for the given stream.

Removing a stream from the loop that has already been removed or trying
to remove a stream that was never added or is invalid has no effect.

#### removeWriteStream()

The `removeWriteStream(resource $stream): void` method can be used to
remove the write event listener for the given stream.

Removing a stream from the loop that has already been removed or trying
to remove a stream that was never added or is invalid has no effect.

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project follows [SemVer](https://semver.org/).
This will install the latest supported version:

```bash
$ composer require react/event-loop:^1.1.1
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on legacy PHP 5.3 through current PHP 7+ and
HHVM.
It's *highly recommended to use PHP 7+* for this project.

Installing any of the event loop extensions is suggested, but entirely optional.
See also [event loop implementations](#loop-implementations) for more details.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```

## License

MIT, see [LICENSE file](LICENSE).

## More

* See our [Stream component](https://github.com/reactphp/stream) for more
  information on how streams are used in real-world applications.
* See our [users wiki](https://github.com/reactphp/react/wiki/Users) and the
  [dependents on Packagist](https://packagist.org/packages/react/event-loop/dependents)
  for a list of packages that use the EventLoop in real-world applications.
