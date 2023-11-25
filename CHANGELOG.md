# Changelog

## [Unreleased]

### Fixed

- Fix reading frames when reading triggers the stream end

## 2.3.0 - 2023-10-25

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
