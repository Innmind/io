---
hide:
    - toc
---

# Reading from a file by lines

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
