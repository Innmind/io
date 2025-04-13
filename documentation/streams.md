---
hide:
    - navigation
---

# Streams

You can use streams by passing a `resource` to this package like so:

```php
use Innmind\IO\IO;

$stream = IO::fromAmbientAuthority()
    ->streams()
    ->acquire(\STDIN);
```

## Read

```php
$read = $stream->read();
```

### `->nonBlocking()`

```php
$read = $read->nonBlocking();
```

This will configure the stream to be non blocking when reading from it. This means that it will return as soon as possible with `string`s shorter than the expected size.

### `->toEncoding()`

```php
use Innmind\Immutable\Str;

$read = $read->toEncoding(Str\Encoding::ascii);
```

This changes the encoding used in all `Str` values returned. By default it's `Str\Encoding::utf8`.

### `->watch()`

```php
$read = $read->watch();
```

This makes sure the stream being read is _ready_, meaning there's data to be read. And it will wait forever until the stream is ready.

This is default behaviour.

### `->timeoutAfter()`

```php
use Innmind\TimeContinuum\Period;

$read = $read->timeoutAfter(Period::second(1));
```

Like `->watch()` it will wait for the stream to be ready before being read, except that if it's not ready within `1` second it will fail the read operation.

### `->poll()`

This is a shortcut to `->timeoutAfter(Period::second(0))`. This means that if the stream is not ready right away when read it will fail.

### `->pool()`

```php
$pool = $stream1
    ->pool('a')
    ->with('b', $stream2);
```

This method allows to combine multiple streams that will be read together.

Here `a` and `b` are ids used to reference from which stream the read data comes from. You can use any type you want.

Then you can read available chunks from this pool like this:

```php
use Innmind\Immutable\Pair;

$pool
    ->chunks()
    ->foreach(static fn(Pair $chunk) => match ($chunk->key()) {
        'a' => doSomethingWithStream1($chunk->value()),
        'b' => doSomethingWithStream2($chunk->value()),
    });
```

### `->frames()`

This is the main way to read data from streams.

```php
use Innmind\IO\Frame;

$frames = $read->frames(Frame::line());
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

## Write

```php
$write = $stream->write();
```

### `->sink()`

```php
use Innmind\Immutable\{
    Sequence,
    Str,
};

$successful = $write->sink(
    Sequence::of(
        Str::of('chunk 1'),
        Str::of("new line \n"),
        Str::of('chunk 2'),
        Str::of('etc...'),
    ),
);
```

This will write each `Str` one after the other to the stream.

`#!php $successful` is an [`Attempt` monad](https://innmind.org/Immutable/structures/attempt/). It will contain an error if it failed to write one the chunks.

Since the data to write is expressed with a `Sequence`, you can use a [lazy one](https://innmind.org/Immutable/structures/sequence/#lazy) to write data that may not fit in memory.
