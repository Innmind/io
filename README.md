# io

[![Build Status](https://github.com/innmind/io/workflows/CI/badge.svg?branch=master)](https://github.com/innmind/io/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/io/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/io)
[![Type Coverage](https://shepherd.dev/github/innmind/io/coverage.svg)](https://shepherd.dev/github/innmind/io)

High level abstraction to work with files and sockets in a declarative way.

## Installation

```sh
composer require innmind/io
```

## Usage

### Reading from a file by chunks

```php
use Innmind\IO\IO;
use Innmind\Url\Path;
use Innmind\Immutable\Str;

$chunks = IO::fromAmbienAuthority()
    ->files()
    ->read(Path::of('/some/file.ext'))
    ->toEncoding(Str\Encoding::ascii)
    ->chunks(8192); // max length of each chunk
```

The `$chunks` variable is a `Innmind\Innmutable\Sequence` containing `Innmind\Immutable\Str` values, where each value is of a maximum length of `8192` bytes.

### Reading from a file by lines

```php
use Innmind\IO\IO;
use Innmind\Url\Path;
use Innmind\Immutable\Str;

$lines = IO::fromAmbienAuthority()
    ->files()
    ->read(Path::of('/some/file.ext'))
    ->toEncoding(Str\Encoding::ascii)
    ->lines();
```

This is the same as reading by chunks (described above) except that the delimiter is the end of line character `\n`.

### Reading from a socket

```php
use Innmind\IO\{
    IO,
    Frame,
    Sockets\Internet\Transport,
};
use Innmind\TimeContinuum\Period;
use Innmind\Url\Url;

$status = IO::fromAmbienAuthority()
    ->sockets()
    ->clients()
    ->internet(
        Transport::tcp(),
        Url::of('https://github.com')->authority(),
    )
    ->map(
        static fn($socket) => $socket
            ->timeoutAfter(Period::second(1))
            ->frames(Frame::line()),
    )
    ->flatMap(static fn($frames) => $frames->one())
    ->unwrap()
    ->toString();
```

This example opens a `tcp` connection to `github.com` and will wait `1` second for the server to respond. If the server responds it will read the first line sent and assign it in `$status` (it should be `HTTP/2 200`).

If the server doesn't respond within the timeout or an entire line is not sent then this will throw an exception (when `->unwrap()` is called).

If you want to wait forever for the server to respond you can replace `->timeoutAfter()` by `->watch()`.

### Reading from a socket with a periodic heartbeat

```php
use Innmind\IO\{
    IO,
    Frame,
    Sockets\Internet\Transport,
};
use Innmind\TimeContinuum\Period;
use Innmind\Url\Url;
use Innmind\Immutable\{
    Str,
    Sequence,
};

$status = IO::fromAmbienAuthority()
    ->sockets()
    ->clients()
    ->internet(
        Transport::tcp(),
        Url::of('https://github.com')->authority(),
    )
    ->map(
        static fn($socket) => $socket
            ->timeoutAfter(Period::second(1))
            ->heartbeatWith(static fn() => Sequence::of(Str::of('heartbeat')))
            ->frames(Frame::line()),
    )
    ->flatMap(static fn($frames) => $frames->one())
    ->unwrap()
    ->toString();
```

This is the same thing as the previous example except that it will send `heartbeat` through the socket every second until the server send a line.

You can call `->abortWhen()` after `->heartbeatWith()` to determine when to stop sending a heartbeat.
