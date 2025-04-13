---
hide:
    - toc
---

# Reading from a socket with a periodic heartbeat

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

You can call `#!php ->abortWhen()` after `#!php ->heartbeatWith()` to determine when to stop sending a heartbeat.
