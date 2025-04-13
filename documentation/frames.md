---
hide:
    - navigation
---

# Frames

A `Frame` is the declaration of the data you want to read.

This is an immutable structure that you can compose any way you like.

The declarative approach allows you to split the responsibility between declaring the shape of data you expect and the actual read operation.

## `::chunk()`

This defines a string of a specified length.

```php
use Innmind\IO\Frame;

$chunk = Frame::chunk(512)->strict();
// or
$chunk = Frame::chunk(512)->loose();
```

- `->strict()` means that if the read data is not of the specified length it will fail
- `->loose()` means that you allow the read data to be shorter than the specified length

## `::line()`

This defines a string ending with `\n`.

```php
use Innmind\IO\Frame;

$line = Frame::line();
```

## `::sequence()`

This defines a lazy `Sequence` of other frames.

```php
use Innmind\IO\Frame;

$lines = Frame::sequence(Frame::line());
```

??? note
    Since the `Sequence` is lazy the error handling as to be done for each value.

    ```php
    use Innmind\Immutable\{
        Sequence,
        Str,
        Attempt,
    };

    $lines = $lines->map(static fn(Sequence $lines) => $lines->map(
        static fn(Attempt $line): Str $line->unwrap(),
    ));
    ```

    Here we throw an exception by calling `->unwrap()` is one of the lines failed to be read.

## `::compose()`

This allows to combine multiple frames together.

```php
use Innmind\IO\Frame;
use Innmind\Immutable\Str;

$request = Frame::compose(
    static fn(Str $first, Str $second) => new HttpRequest($first, $second),
    Frame::line(),
    Frame::line(),
);
```

Here it will read 2 lines and create an imaginary `HttpRequest` with them.

Each new frame passed as argument will add an argument to the callable.

## `::buffer()`

This is useful when you have a strict protocol where you know the length to read. This will read the whole length once and keep it in memory. This avoids watching the stream/socket too much.

```php
use Innmind\IO\Frame;

$frame = Frame::buffer(
    100,
    Frame::compose(
        static fn(Str $a, Str $b, Str $c) => [$a, $b, $c],
        Frame::chunk(20)->strict(),
        Frame::chunk(20)->strict(),
        Frame::chunk(60)->strict(),
    ),
);
```

This creates a frame containing 3 chunks. But instead of reading from the stream/socket 3 times it will do it only once.

This is to improve performance, it doesn't have any behaviour impact.

## `->filter()`

This method allows you to make sure the read data matches a condition. If the callable returns `false` it will fail the read.

```php
use Innmind\IO\Frame;
use Innmind\Immutable\Str;

$nonErroneousFrame = Frame::line()->filter(
    static fn(Str $line) => !$line->contains('err:'),
);
```

## `->map()`

This method allows to transform the read data to any type you want.

```php
use Innmind\IO\Frame;
use Innmind\Immutable\Str;

$int = Frame::line()
    ->map(static fn(Str $line) => $line->trim()->toString())
    ->map(static fn(string $line) => (int) $line);
```

## `->flatMap()`

This method allows to dynamically determine the next frame from a previous one.

```php
use Innmind\IO\Frame;
use Innmind\Immutable\Str;

$value = Frame::chunk(1)
    ->strict()
    ->map(static function(Str $chunk) {
        /** @var int<0, 255> $octet */
        [, $octet] = \unpack('C', $chunk->toString());

        return $octet;
    })
    ->flatMap(static fn(int $size) => Frame::chunk($size)->strict());
```

Here we read a first chunk of known length that contains the size of the rest of the data to read.

??? info
    This is a common pattern used in binary protocols.
