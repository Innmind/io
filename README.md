# io

[![Build Status](https://github.com/innmind/io/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/innmind/io/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/innmind/io/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/io)
[![Type Coverage](https://shepherd.dev/github/innmind/io/coverage.svg)](https://shepherd.dev/github/innmind/io)

High level abstraction to work with files and sockets in a declarative way.

## Installation

```sh
composer require innmind/io
```

## Usage

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

The `$chunks` variable is a `Innmind\Innmutable\Sequence` containing `Innmind\Immutable\Str` values, where each value is of a maximum length of `8192` bytes.

## Documentation

Full documentation can be found at <https://innmind.org/io/>.
