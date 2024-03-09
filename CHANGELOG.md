# Changelog

## [Unreleased]

### Added

- `Innmind\IO\Sockets::servers()`
- `Innmind\IO\Sockets\Servers`

## 2.6.0 - 2024-03-09

### Added

- `Innmind\IO\Sockets\Client::abortWhen()`

## 2.5.0 - 2023-12-10

### Added

- `Innmind\IO\IO::sockets()`

## 2.4.1 - 2023-12-03

### Fixed

- `Innmind\IO\Readable\Frame\NoOp` constructor type
- Frames types transitions via `::filter()`, `::map()` and `::flatMap()`

## 2.4.0 - 2023-12-03

### Added

- `Innmind\IO\Readable\Frame\NoOp`

### Changed

- `Innmind\IO\Readable\Frame` transformations methods are declared mutation free
- `Innmind\IO\Readable\Frame\Chunk` makes sure the read chunk is of the expected size

## 2.3.1 - 2023-11-25

### Fixed

- Fix reading frames when reading triggers the stream end

## 2.3.0 - 2023-11-25

### Added

- `Innmind\IO\Readable\Stream::unwrap()`
- `Innmind\IO\Readable\Stream::frames()`
- `Innmind\IO\Readable\Frame`
- `Innmind\IO\Readable\Frames`
- `Innmind\IO\Readable\Frame\Chunk`
- `Innmind\IO\Readable\Frame\Chunks`
- `Innmind\IO\Readable\Frame\Composite`
- `Innmind\IO\Readable\Frame\FlatMap`
- `Innmind\IO\Readable\Frame\Line`
- `Innmind\IO\Readable\Frame\Map`
- `Innmind\IO\Readable\Frame\Sequence`

## 2.2.0 - 2023-10-15

### Added

- `Innmind\IO\Readable\Stream::size()`

## 2.1.0 - 2023-10-15

### Added

- `Innmind\IO\Readable\Chunks::lazy()`
- `Innmind\IO\Readable\Chunks\Lazy`
- `Innmind\IO\Readable\Stream::lines()`
- `Innmind\IO\Readable\Lines`

## 2.0.0 - 2023-09-17

### Added

- Support for `innmind/immutable:~5.0`

### Changed

- All encodings are represented with `Innmind\Immutable\Str\Encoding`

### Removed

- Support for PHP `8.1`
