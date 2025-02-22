<?php
declare(strict_types = 1);

use Innmind\IO\{
    IO,
    Files\Read,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Sequence,
    Monoid\Concat,
    SideEffect,
};
use Innmind\BlackBox\Set;

return static function() {
    // Here we make sure to only use characters that are "reversible". Writing
    // and then reading should return the exact same character.
    $string = Set\Strings::madeOf(
        Set\Unicode::any()
            ->map(IntlChar::ord(...))
            ->filter(\is_int(...))
            ->map(IntlChar::chr(...))
            ->filter(\is_string(...)),
    );
    // We reduce the length of strings to avoid exhausting the allowed memory.
    $strings = Set\Either::any(
        Set\Sequence::of($string->between(0, 20)),
        Set\Sequence::of($string)->between(0, 20),
    );

    yield proof(
        'IO::files()->read()->chunks()',
        given(
            $strings,
            Set\Integers::between(1, 100),
        ),
        static function($assert, $chunks, $size) {
            $tmp = \tempnam(\sys_get_temp_dir(), 'innmind/io');
            $data = \implode('', $chunks);
            \file_put_contents($tmp, $data);

            $loaded = IO::fromAmbientAuthority()
                ->files()
                ->read(Path::of($tmp))
                ->chunks($size)
                ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii));

            $assert
                ->number($loaded->size())
                ->int()
                ->greaterThan(0);
            $loaded
                ->dropEnd(1)
                ->foreach(static fn($chunk) => $assert->same(
                    $size,
                    $chunk->length(),
                ));
            $assert
                ->number($loaded->last()->match(
                    static fn($chunk) => $chunk->length(),
                    static fn() => null,
                ))
                ->int()
                ->lessThanOrEqual($size);

            $assert->same(
                $data,
                $loaded
                    ->fold(new Concat)
                    ->toString(),
            );
        },
    );

    yield proof(
        'IO::files()->read()->toEncoding()->chunks()',
        given(
            $strings,
            Set\Integers::between(1, 100),
            Set\Elements::of(...Str\Encoding::cases()),
        ),
        static function($assert, $chunks, $size, $encoding) {
            $tmp = \tempnam(\sys_get_temp_dir(), 'innmind/io');
            $data = \implode('', $chunks);
            \file_put_contents($tmp, $data);

            IO::fromAmbientAuthority()
                ->files()
                ->read(Path::of($tmp))
                ->toEncoding($encoding)
                ->chunks($size)
                ->foreach(static fn($chunk) => $assert->same(
                    $encoding,
                    $chunk->encoding(),
                ));
        },
    );

    yield proof(
        'IO::files()->read()->lines()',
        given(
            Set\Either::any(
                Set\Sequence::of($string->between(0, 20)->filter(
                    static fn($line) => !\str_contains($line, "\n"),
                )),
                Set\Sequence::of($string->filter(
                    static fn($line) => !\str_contains($line, "\n"),
                ))->between(0, 20),
            ),
        ),
        static function($assert, $lines) {
            $tmp = \tempnam(\sys_get_temp_dir(), 'innmind/io');
            $data = \implode("\n", $lines);
            \file_put_contents($tmp, $data);

            $loaded = IO::fromAmbientAuthority()
                ->files()
                ->read(Path::of($tmp))
                ->lines();

            $assert
                ->number($loaded->size())
                ->int()
                ->greaterThan(0);
            $loaded
                ->dropEnd(1)
                ->foreach(static fn($line) => $assert->true(
                    $line->endsWith("\n"),
                ));
            $lastLine = $loaded->last()->match(
                static fn($line) => $line,
                static fn() => null,
            );
            $assert->object($lastLine);
            $assert->false($lastLine->endsWith("\n"));

            $assert->same(
                $data,
                $loaded
                    ->fold(new Concat)
                    ->toString(),
            );

            $expected = match (\count($lines)) {
                0 => [''],
                default => $lines,
            };

            $assert->same(
                $expected,
                $loaded
                    ->dropEnd(1)
                    ->map(static fn($line) => $line->dropEnd(1))
                    ->append($loaded->takeEnd(1))
                    ->map(static fn($line) => $line->toString())
                    ->toList(),
            );
        },
    );

    yield proof(
        'IO::files()->read()->toEncoding()->lines()',
        given(
            $strings,
            Set\Elements::of(...Str\Encoding::cases()),
        ),
        static function($assert, $lines, $encoding) {
            $tmp = \tempnam(\sys_get_temp_dir(), 'innmind/io');
            $data = \implode("\n", $lines);
            \file_put_contents($tmp, $data);

            IO::fromAmbientAuthority()
                ->files()
                ->read(Path::of($tmp))
                ->toEncoding($encoding)
                ->lines()
                ->foreach(static fn($line) => $assert->same(
                    $encoding,
                    $line->encoding(),
                ));
        },
    );

    yield proof(
        'IO::files()->read()->size()',
        given($strings),
        static function($assert, $chunks) {
            $tmp = \tempnam(\sys_get_temp_dir(), 'innmind/io');
            $data = \implode('', $chunks);
            \file_put_contents($tmp, $data);

            $size = IO::fromAmbientAuthority()
                ->files()
                ->read(Path::of($tmp))
                ->size()
                ->match(
                    static fn($size) => $size->toInt(),
                    static fn() => null,
                );

            $assert
                ->number($size)
                ->int();
            $assert->same(\strlen($data), $size);
        },
    );

    yield proof(
        'IO::files()->write()->sink()',
        given(
            $strings,
            Set\Elements::of(...Str\Encoding::cases()),
        ),
        static function($assert, $chunks, $encoding) {
            $tmp = \tempnam(\sys_get_temp_dir(), 'innmind/io');

            $sideEffect = IO::fromAmbientAuthority()
                ->files()
                ->write(Path::of($tmp))
                ->sink(
                    Sequence::of(...$chunks)
                        ->map(Str::of(...))
                        ->map(static fn($chunk) => $chunk->toEncoding($encoding)),
                )
                ->match(
                    static fn($sideEffect) => $sideEffect,
                    static fn() => null,
                );

            $assert
                ->object($sideEffect)
                ->instance(SideEffect::class);
            $assert->same(
                \implode('', $chunks),
                \file_get_contents($tmp),
            );
        },
    );

    yield proof(
        'IO::files()->temporary()',
        given($strings),
        static function($assert, $chunks) {
            $read = IO::fromAmbientAuthority()
                ->files()
                ->temporary(Sequence::of(...$chunks)->map(Str::of(...)))
                ->match(
                    static fn($read) => $read,
                    static fn() => null,
                );

            $assert
                ->object($read)
                ->instance(Read::class);

            $expected = \implode('', $chunks);
            $assert->same(
                $expected,
                $read
                    ->lines()
                    ->fold(new Concat)
                    ->toString(),
            );
            $assert->same(
                $expected,
                $read
                    ->lines()
                    ->fold(new Concat)
                    ->toString(),
                'Temporary file should be accessible multiple times',
            );
        },
    );
};
