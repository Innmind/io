---
hide:
    - toc
---

# Reading from a file by chunks

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

The `#!php $chunks` variable is a `Innmind\Innmutable\Sequence` containing `Innmind\Immutable\Str` values, where each value is of a maximum length of `#!php 8192` bytes.
