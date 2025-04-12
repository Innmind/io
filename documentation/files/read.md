
# Read files

## By chunks

```php
use Innmind\IO\IO;
use Innmind\Url\Path;

$chunks = IO::fromAmbientAuthority()
    ->files()
    ->read(Path::of('/path/to/file.ext'))
    ->chunks(8192);
```

This will produce a lazy [`Sequence`](https://innmind.org/Immutable/structures/sequence/) containing [`Str` objects](https://innmind.org/Immutable/structures/str/) with each value with a length of at most `8192`.

## By lines

```php
use Innmind\IO\IO;
use Innmind\Url\Path;

$chunks = IO::fromAmbientAuthority()
    ->files()
    ->read(Path::of('/path/to/file.ext'))
    ->lines();
```

This will produce a lazy [`Sequence`](https://innmind.org/Immutable/structures/sequence/) containing [`Str` objects](https://innmind.org/Immutable/structures/str/). Each line ends with `\n`, except the last one.

## With a specific encoding

By default when reading a file the `Str` produced uses the `Str\Encoding::utf8`. You can change that like this:

```php
use Innmind\IO\IO;
use Innmind\Url\Path;
use Innmind\Immutable\Str;

$chunks = IO::fromAmbientAuthority()
    ->files()
    ->read(Path::of('/path/to/file.ext'))
    ->toEncoding(Str\Encoding::ascii)
    ->lines();
```
