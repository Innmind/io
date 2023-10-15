# io

[![Build Status](https://github.com/innmind/io/workflows/CI/badge.svg?branch=master)](https://github.com/innmind/io/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/io/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/io)
[![Type Coverage](https://shepherd.dev/github/innmind/io/coverage.svg)](https://shepherd.dev/github/innmind/io)

High level abstraction on top of [`innmind/stream`](https://github.com/Innmind/Stream) to work with streams in a more functional way.

## Installation

```sh
composer require innmind/io
```

## Usage

### Reading from a stream by chunks

```php
use Innmind\IO\IO;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Stream\Streams;
use Innmind\Immutable\Str;

$streams = Streams::fromAmbienAuthority();
$io = IO::of($os->sockets()->watch(...));
$chunks = $io
    ->readable()
    ->wrap(
        $streams
            ->readable()
            ->acquire(\fopen('/some/file.ext', 'r')),
    )
    ->toEncoding(Str\Encoding::ascii)
    // or call ->watch() to wait forever for the stream to be ready before
    // reading from it
    ->timeoutAfter(ElapsedPeriod::of(1_000))
    ->chunks(8192) // max length of each chunk
    ->lazy()
    ->sequence();
```

The `$chunks` variable is a `Innmind\Innmutable\Sequence` containing `Innmind\Immutable\Str` values, where each value is of a maximum length of `8192` bytes. Before a value is yielded it will make sure data is available before reading from the stream. If no data is available within `1` second the `Sequence` will throw an exception saying it can't read from the stream, if you don't want it to throw replace `timeoutAfter()` by `watch()` so it will wait as long as it needs to.

### Reading from a stream by lines

```php
use Innmind\IO\IO;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Stream\Streams;
use Innmind\Immutable\Str;

$streams = Streams::fromAmbienAuthority();
$io = IO::of($os->sockets()->watch(...));
$lines = $io
    ->readable()
    ->wrap(
        $streams
            ->readable()
            ->acquire(\fopen('/some/file.ext', 'r')),
    )
    ->toEncoding(Str\Encoding::ascii)
    // or call ->watch() to wait forever for the stream to be ready before
    // reading from it
    ->timeoutAfter(ElapsedPeriod::of(1_000))
    ->lines()
    ->lazy()
    ->sequence();
```

This is the same as reading by chunks (described above) except that the delimiter is the end of line character `\n`.

### Reading from a stream

```php
use Innmind\IO\IO;
use Innmind\OperatingSystem\Factory;
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
use Innmind\Stream\Streams;
use Innmind\Socket\Address\Unix;
use Innmind\Immutable\{
    Str,
    Fold,
    Either,
};

$os = Factory::build();
$streams = Streams::fromAmbienAuthority();
$io = IO::of($os->sockets()->watch(...));
$io
    ->readable()
    ->wrap(
        $os
            ->sockets()
            ->connectTo(Unix::of('/some/socket')),
    )
    ->toEncoding('ASCII')
    // or call ->watch() to wait forever for the stream to be ready before
    // reading from it
    ->timeoutAfter(ElapsedPeriod::of(1_000))
    ->chunks(8192) // max length of each chunk
    ->fold(
        Fold::with([]),
        static function(array $chunks, Str $chunk) {
            $chunks[] = $chunk->toString();

            if ($chunk->contains('quit')) {
                return Fold::result($chunks);
            }

            if ($chunk->contains('throw')) {
                return Fold::fail('some error');
            }

            return Fold::with($chunks);
        },
    )
    ->match(
        static fn(Either $result) => $result->match(
            static fn(array $chunks) => doStuff($chunks),
            static fn(string $error) => throw new \Exception($error), // $error === 'some error'
        ),
        static fn() => throw new \RuntimeException('Failed to read from the stream or it timed out'),
    );
```

This example will:
- open the local socket `/some/socket`
- watch the socket to be ready for `1` second before it times out each time it tries to read from it
- read chunks of a maximum length of `8192`
- use the encoding `ASCII`
- call the function passed to `->fold()` each time a chunk is read
- it will continue reading from the stream until one of the chunks contains `quit` or `throw`
- return a `Maybe<Either<string, list<string>>>`
    - contains nothing when it failed to read from the stream or it timed out
    - `string` is the value passed to `Fold::fail()`
    - `list<string>` is the value passed to `Fold::result()`

You can think of this `fold` operation as a reduce where you can control when to stop iterating by return either `Fold::fail()` or `Fold::result()`.

**Note**: this example use [`innmind/operating-system`](https://github.com/Innmind/OperatingSystem)
