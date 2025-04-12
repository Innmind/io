
# Write to files

```php
use Innmind\IO\IO;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Str,
};

$successful = IO::fromAmbientAuthority()
    ->files()
    ->write(Path::of('/path/to/file.ext'))
    ->sink(Sequence::of(
        Str::of('chunk 1'),
        Str::of("new line \n"),
        Str::of('chunk 2'),
        Str::of('etc...'),
    ));
```

`#!php $successful` is an instance of [`Attempt<SideEffect>`](https://innmind.org/Immutable/structures/attempt/).

Since the data to write is expressed with a `Sequence`, you can use a [lazy one](https://innmind.org/Immutable/structures/sequence/#lazy) to create files that may not fit in memory.
