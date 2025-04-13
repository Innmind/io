---
hide:
    - toc
---

# Reading from a socket

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

This example opens a `tcp` connection to `github.com` and will wait `1` second for the server to respond. If the server responds it will read the first line sent and assign it in `#!php $status` (it should be `HTTP/2 200`).

If the server doesn't respond within the timeout or an entire line is not sent then this will throw an exception (when `#!php ->unwrap()` is called).

If you want to wait forever for the server to respond you can replace `#!php ->timeoutAfter()` by `#!php ->watch()`.
