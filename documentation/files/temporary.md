
# Temporary

```php
use Innmind\IO\IO;
use Innmind\Immutable\{
    Sequence,
    Str,
};

$temporary = IO::fromAmbientAuthority()
    ->files()
    ->temporary(Sequence::of(
        Str::of('chunk 1'),
        Str::of("new line \n"),
        Str::of('chunk 2'),
        Str::of('etc...'),
    ))
    ->unwrap();
```

This creates a temporary file without having to think about where to store it.

You can then use it in 2 ways:

=== "Like a normal file"
    ```php
    $temporary
        ->read()
        ->chunks(8192);
    // or
    $temporary
        ->read()
        ->lines();
    ```

=== "Chunk by chunk"
    ```php
    $pull = $temporary->pull();

    do {
        $chunk = $pull
            ->chunk(512)
            ->unwrap();
        doSomething($chunk);
    } while (!$chunk->empty());
    ```

    The call to `->chunk()` is stateful as it remembers the position in the file. This means that you can only read forward, and only once.
