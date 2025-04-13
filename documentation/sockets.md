---
hide:
    - navigation
---

# Sockets

## Servers

### Internet

```php
use Innmind\IO\{
    IO,
    Sockets\Internet\Transport,
};
use Innmind\IP\IP;
use Innmind\Url\Authority\Port;

$server = IO::fromAmbientAuthority()
    ->sockets()
    ->servers()
    ->internet(
        Transport::tcp(),
        IP::v4('0.0.0.0'),
        Port::of(8080),
    )
    ->unwrap();
```

This will open the port `8080` on the machine and listen tcp connections coming from inside or outside the machine.

### Unix

```php
use Innmind\IO\{
    IO,
    Sockets\Unix\Address,
};
use Innmind\Url\Path;

$server = IO::fromAmbientAuthority()
    ->sockets()
    ->servers()
    ->unix(Address::of(
        Path::of('/path/to/socket'),
    ))
    ->unwrap();
```

This will create a `/path/to/socket.sock` socket.

If the socket already exists, if your script crashed for example, then this code will fail. You can fix that by using `->takeOver()` instead.

```php hl_lines="10"
use Innmind\IO\{
    IO,
    Sockets\Unix\Address,
};
use Innmind\Url\Path;

$server = IO::fromAmbientAuthority()
    ->sockets()
    ->servers()
    ->takeOver(Address::of(
        Path::of('/path/to/socket'),
    ))
    ->unwrap();
```

### `->watch()`

```php
$server = $server->watch();
```

This makes sure the socket is ready before trying to accept a new connection.

This is the default behaviour.

### `->timeoutAfter()`

```php
use Innmind\TimeContinuum\Period;

$server = $server->timeoutAfter(Period::second(1));
```

Like `->watch()` it will wait for the socket to be ready before accepting a new connection, except that if no connection came within `1` second it will fail.

### `->accept()`

```php
$client = $server
    ->accept()
    ->unwrap();
```

This returns a new connection made to the server. See below on how to use this client.

### `->pool()`

This allows to watch for multiple servers at once.

```php
$clients = $server1
    ->pool($server2)
    ->accept();
```

`#!php $clients` is a `Innmind\Immutable\Sequence<Innmind\IO\Sockets\Clients\Client>`.

## Clients

### Internet

```php
use Innmind\IO\{
    IO,
    Sockets\Internet\Transport,
};
use Innmind\Url\Url;

$client = IO::fromAmbientAuthority()
    ->sockets()
    ->clients()
    ->internet(
        Transport::tcp(),
        Url::of('http://example.com')->authority(),
    )
    ->unwrap();
```

### Unix

```php
use Innmind\IO\{
    IO,
    Sockets\Unix\Address,
};
use Innmind\Url\Path;

$client = IO::fromAmbientAuthority()
    ->sockets()
    ->clients()
    ->unix(Address::of(
        Path::of('/path/to/socket'),
    ))
    ->unwrap();
```

### `->toEncoding()`

```php
use Innmind\Immutable\Str;

$client = $client->toEncoding(Str\Encoding::acsii);
```

This change the encoding of strings read. By default it uses `Str\Encoding::utf8`.

### `->watch()`

```php
$client = $client->watch();
```

This makes sure the socket being read is _ready_, meaning there's data to be read. And it will wait forever until the socket is ready.

This is default behaviour.

### `->timeoutAfter()`

```php
use Innmind\TimeContinuum\Period;

$client = $client->timeoutAfter(Period::second(1));
```

Like `->watch()` it will wait for the socket to be ready before being read, except that if it's not ready within `1` second it will fail the read operation.

### `->poll()`

This is a shortcut to `->timeoutAfter(Period::second(0))`. This means that if the socket is not ready right away when read it will fail.

### `->heartbeatWith()`

```php
use Innmind\Immutable\{
    Sequence,
    Str,
};

$client = $client->heartbeatWith(
    static fn() => Sequence::of(Str::of('heartbeat')),
);
```

When reading from the socket it will send the data provided by the callable through the socket. It does this when watching for the socket to be ready times out.

!!! warning ""
    This means you must use `->timeoutAfter()` to use this feature, otherwise it will never send the heartbeat message.

When a heartbeat message is sent then the client will resume watching the socket.

### `->abortWhen()`

This method should be used in tandem with `->heartbeatWith()`. The heartbeat mechanism essentially creates an infinite loop of watching the socket to be ready to be read.

This method allows you to specify a condition to break this infinite loop.

```php
$client = $client->abortWhen(
    static fn(): bool => someCondition(),
);
```

Usually the condition to use is a process signal (like `SIGINT`).

### `->frames()`

This is the main way to read data from sockets.

```php
use Innmind\IO\Frame;

$frames = $client->frames(Frame::line());
```

Then you can either read:

=== "One frame"
    ```php
    $line = $frames
        ->one()
        ->unwrap();
    ```

    `#!php $line` is an instance of `Innmind\Immutable\Str`. `->one()` returns an [`Attempt` monad](https://innmind.org/Immutable/structures/attempt/) that we `->unwrap()` here, meaning that if it fails to read the frame then it will throw an exception.

=== "Multiple frames"
    ```php
    $lines = $frames
        ->lazy()
        ->sequence();
    ```

    `#!php $lines` is a `Innmind\Immutable\Sequence<Innmind\Immutable\Str>`.

    Since the sequence is lazy this means that you may read some lines and then an exception is thrown if it fails to read a line.

!!! success ""
    See the [Frames section](frames.md) to learn how to create frames.

### `->sink()`

```php
use Innmind\Immutable\{
    Sequence,
    Str,
};

$successful = $client->sink(
    Sequence::of(
        Str::of('chunk 1'),
        Str::of("new line \n"),
        Str::of('chunk 2'),
        Str::of('etc...'),
    ),
);
```

This will send each `Str` one after the other through the socket.

`#!php $successful` is an [`Attempt` monad](https://innmind.org/Immutable/structures/attempt/). It will contain an error if it failed to send one the chunks.

Since the data to send is expressed with a `Sequence`, you can use a [lazy one](https://innmind.org/Immutable/structures/sequence/#lazy) to send data that may not fit in memory.

## Pair

```php
[$a, $b] = IO::fromAmbientAuthority()
    ->sockets()
    ->pair()
    ->unwrap();
```

This creates a pair of socket clients link to one another. This is useful for inter process communication.
