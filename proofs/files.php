<?php
declare(strict_types = 1);

use Innmind\IO\{
    IO,
    Files\Read,
    Files\Temporary,
    Files\Temporary\Pull,
    Files\Directory,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Sequence,
    Monoid\Concat,
    SideEffect,
};
use Innmind\BlackBox\{
    Set,
    Prove,
};

return static function(Prove $prove) {
    // Here we make sure to only use characters that are "reversible". Writing
    // and then reading should return the exact same character.
    $string = Set::strings()->madeOf(
        Set::strings()
            ->unicode()
            ->char()
            ->map(IntlChar::ord(...))
            ->filter(\is_int(...))
            ->map(IntlChar::chr(...))
            ->filter(\is_string(...)),
    );
    // We reduce the length of strings to avoid exhausting the allowed memory.
    $strings = Set::either(
        Set::sequence($string->between(0, 20)),
        Set::sequence($string)->between(0, 20),
    );

    yield $prove
        ->proof('IO::files()->read()->chunks()')
        ->given(
            $strings,
            Set::integers()->between(1, 100),
        )
        ->test(static function($assert, $chunks, $size) {
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
            $_ = $loaded
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
                    ->fold(Concat::monoid)
                    ->toString(),
            );
        });

    yield $prove
        ->proof('IO::files()->read()->toEncoding()->chunks()')
        ->given(
            $strings,
            Set::integers()->between(1, 100),
            Set::of(...Str\Encoding::cases()),
        )
        ->test(static function($assert, $chunks, $size, $encoding) {
            $tmp = \tempnam(\sys_get_temp_dir(), 'innmind/io');
            $data = \implode('', $chunks);
            \file_put_contents($tmp, $data);

            $_ = IO::fromAmbientAuthority()
                ->files()
                ->read(Path::of($tmp))
                ->toEncoding($encoding)
                ->chunks($size)
                ->foreach(static fn($chunk) => $assert->same(
                    $encoding,
                    $chunk->encoding(),
                ));
        });

    yield $prove
        ->proof('IO::files()->read()->lines()')
        ->given(
            Set::either(
                Set::sequence($string->between(0, 20)->filter(
                    static fn($line) => !\str_contains($line, "\n"),
                )),
                Set::sequence($string->filter(
                    static fn($line) => !\str_contains($line, "\n"),
                ))->between(0, 20),
            ),
        )
        ->test(static function($assert, $lines) {
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
            $_ = $loaded
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
                    ->fold(Concat::monoid)
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
        });

    yield $prove
        ->proof('IO::files()->read()->toEncoding()->lines()')
        ->given(
            $strings,
            Set::of(...Str\Encoding::cases()),
        )
        ->test(static function($assert, $lines, $encoding) {
            $tmp = \tempnam(\sys_get_temp_dir(), 'innmind/io');
            $data = \implode("\n", $lines);
            \file_put_contents($tmp, $data);

            $_ = IO::fromAmbientAuthority()
                ->files()
                ->read(Path::of($tmp))
                ->toEncoding($encoding)
                ->lines()
                ->foreach(static fn($line) => $assert->same(
                    $encoding,
                    $line->encoding(),
                ));
        });

    yield $prove
        ->proof('IO::files()->read()->size()')
        ->given($strings)
        ->test(static function($assert, $chunks) {
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
        });

    yield $prove
        ->proof('IO::files()->write()->sink()')
        ->given(
            $strings,
            Set::of(...Str\Encoding::cases()),
        )
        ->test(static function($assert, $chunks, $encoding) {
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
        });

    yield $prove
        ->proof('IO::files()->write()->watch()->sink()')
        ->given(
            $strings,
            Set::of(...Str\Encoding::cases()),
        )
        ->test(static function($assert, $chunks, $encoding) {
            $tmp = \tempnam(\sys_get_temp_dir(), 'innmind/io');

            $sideEffect = IO::fromAmbientAuthority()
                ->files()
                ->write(Path::of($tmp))
                ->watch()
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
        });

    yield $prove
        ->proof('IO::files()->temporary()->read()')
        ->given($strings)
        ->test(static function($assert, $chunks) {
            $read = IO::fromAmbientAuthority()
                ->files()
                ->temporary(Sequence::of(...$chunks)->map(Str::of(...)))
                ->map(static fn($temporary) => $temporary->read())
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
                    ->fold(Concat::monoid)
                    ->toString(),
            );
            $assert->same(
                $expected,
                $read
                    ->lines()
                    ->fold(Concat::monoid)
                    ->toString(),
                'Temporary file should be accessible multiple times',
            );
        });

    yield $prove
        ->proof('IO::files()->temporary()->pull()')
        ->given(
            $strings,
            Set::integers()->between(1, 100),
            Set::of(...Str\Encoding::cases()),
        )
        ->test(static function($assert, $chunks, $size, $encoding) {
            $pull = IO::fromAmbientAuthority()
                ->files()
                ->temporary(Sequence::of(...$chunks)->map(Str::of(...)))
                ->flatMap(static fn($temporary) => $temporary->pull())
                ->match(
                    static fn($pull) => $pull->watch()->toEncoding($encoding),
                    static fn() => null,
                );

            $assert
                ->object($pull)
                ->instance(Pull::class);

            $expected = \implode('', $chunks);
            $read = '';

            do {
                $chunk = $pull
                    ->chunk($size)
                    ->unwrap();

                $assert->same($encoding, $chunk->encoding());
                $read .= $chunk->toString();
            } while (!$chunk->empty());

            $assert->same(
                $expected,
                $read,
            );
        });

    yield $prove
        ->proof('IO::files()->temporary()->push()')
        ->given(
            $strings,
            Set::of(...Str\Encoding::cases()),
        )
        ->test(static function($assert, $chunks, $encoding) {
            $tmp = IO::fromAmbientAuthority()
                ->files()
                ->temporary(Sequence::of())
                ->match(
                    static fn($tmp) => $tmp,
                    static fn() => null,
                );

            $assert
                ->object($tmp)
                ->instance(Temporary::class);
            $push = $tmp->push()->watch();

            foreach ($chunks as $chunk) {
                $assert
                    ->object($push->chunk(Str::of($chunk, $encoding))->match(
                        static fn($sideEffect) => $sideEffect,
                        static fn() => null,
                    ))
                    ->instance(SideEffect::class);
            }

            $assert->same(
                \implode('', $chunks),
                $tmp
                    ->read()
                    ->chunks(8192)
                    ->fold(Concat::monoid)
                    ->toString(),
            );
        });

    yield $prove
        ->proof('IO::files()->temporary()->close()')
        ->given($strings)
        ->test(static function($assert, $chunks) {
            $tmp = IO::fromAmbientAuthority()
                ->files()
                ->temporary(Sequence::of(...$chunks)->map(Str::of(...)))
                ->match(
                    static fn($tmp) => $tmp,
                    static fn() => null,
                );

            $assert
                ->object($tmp)
                ->instance(Temporary::class);

            $assert->not()->null(
                $tmp->read()->size()->match(
                    static fn($size) => $size,
                    static fn() => null,
                ),
            );

            $assert
                ->object($tmp->close()->match(
                    static fn($sideEffect) => $sideEffect,
                    static fn() => null,
                ))
                ->instance(SideEffect::class);

            $assert->null(
                $tmp->read()->size()->match(
                    static fn($size) => $size,
                    static fn() => null,
                ),
            );
        });

    yield $prove->test(
        'IO::files()->require()',
        static function($assert) {
            $assert->same(
                42,
                IO::fromAmbientAuthority()
                    ->files()
                    ->require(Path::of('fixtures/to-load.php'))
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $assert->false(
                IO::fromAmbientAuthority()
                    ->files()
                    ->require(Path::of('fixtures/unknown.php'))
                    ->match(
                        static fn() => true,
                        static fn() => false,
                    ),
            );
        },
    );

    yield $prove->test(
        'IO::files() can read a directory containing a #',
        static function($assert) {
            $tmp = \sys_get_temp_dir();
            @\rmdir($tmp.'/innmind/#/');
            @\mkdir($tmp.'/innmind/#/', recursive: true);

            $assert
                ->object(
                    IO::fromAmbientAuthority()
                        ->files()
                        ->access(Path::file($tmp.'/innmind/#'))
                        ->unwrap(),
                )
                ->instance(Directory::class);
        },
    );
};
