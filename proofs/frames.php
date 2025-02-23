<?php
declare(strict_types = 1);

use Innmind\IO\Frame;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
};
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'Frame::just()',
        given(
            Set\Type::any(),
            Set\Nullable::of(Set\Unicode::strings()->map(Str::of(...))),
        ),
        static function($assert, $value, $read) {
            $frame = Frame::just($value);

            $assert->same(
                $value,
                $frame(
                    static fn() => $read,
                    static fn() => $read,
                )->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
        },
    );

    yield proof(
        'Frame::maybe()',
        given(
            Set\Nullable::of(Set\Type::any())->map(Maybe::of(...)),
            Set\Nullable::of(Set\Unicode::strings()->map(Str::of(...))),
        ),
        static function($assert, $value, $read) {
            $frame = Frame::maybe($value);

            $assert->same(
                $value,
                $frame(
                    static fn() => $read,
                    static fn() => $read,
                ),
            );
        },
    );

    yield proof(
        'Frame::chunk()',
        given(
            Set\Unicode::strings()
                ->map(Str::of(...))
                ->map(static fn($str) => $str->toEncoding(Str\Encoding::ascii)),
        ),
        static function($assert, $string) {
            $size = $string->length();
            $frame = Frame::chunk($size);

            $assert->same(
                $string,
                $frame(
                    static function($in) use ($assert, $size, $string) {
                        $assert->same($size, $in);

                        return Maybe::just($string);
                    },
                    static fn() => Maybe::nothing(),
                )->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
            $assert->null(
                $frame(
                    static function($in) use ($assert, $size, $string) {
                        $assert->same($size, $in);

                        return Maybe::nothing();
                    },
                    static fn() => Maybe::just($string),
                )->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
        },
    );

    yield proof(
        'Frame::line()',
        given(
            Set\Unicode::strings()->map(Str::of(...)),
        ),
        static function($assert, $string) {
            $frame = Frame::line();

            $assert->same(
                $string,
                $frame(
                    static fn() => Maybe::nothing(),
                    static fn() => Maybe::just($string),
                )->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
            $assert->null(
                $frame(
                    static fn() => Maybe::just($string),
                    static fn() => Maybe::nothing(),
                )->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
        },
    );

    yield proof(
        'Frame::sequence()',
        given(Set\Sequence::of(
            Set\Unicode::strings()->atLeast(1),
        )),
        static function($assert, $lines) {
            $frame = Frame::sequence(Frame::line());
            $source = Sequence::of(...$lines)
                ->add('')
                ->map(Str::of(...))
                ->map(Maybe::just(...));

            $assert
                ->object(
                    $frame(
                        static fn() => throw new Exception,
                        static fn() => throw new Exception,
                    )->match(
                        static fn($values) => $values,
                        static fn() => null,
                    ),
                )
                ->instance(Sequence::class, 'Frame::sequence() must return a lazy Sequence');
            $read = static function() use (&$source) {
                $first = $source->first()->flatMap(
                    static fn($string) => $string,
                );
                $source = $source->drop(1);

                return $first;
            };

            $frame = $frame
                ->map(
                    static fn($lines) => $lines
                        ->takeWhile(static fn($line) => $line->match(
                            static fn($line) => !$line->empty(),
                            static fn() => false,
                        ))
                        ->sink(Sequence::of())
                        ->maybe(static fn($lines, $line) => $line->map($lines)),
                )
                ->flatMap(Frame::maybe(...));
            $values = $frame(
                static fn() => throw new Exception,
                $read,
            )->match(
                static fn($values) => $values,
                static fn() => null,
            );

            $assert->object($values);
            $assert->same(
                $lines,
                $values
                    ->map(static fn($line) => $line->toString())
                    ->toList(),
            );
        },
    );

    yield proof(
        'Frame::filter()',
        given(
            Set\Unicode::strings()->map(Str::of(...)),
        ),
        static function($assert, $string) {
            $frame = Frame::line();

            $assert->same(
                $string,
                $frame(
                    static fn() => Maybe::nothing(),
                    static fn() => Maybe::just($string),
                )
                    ->filter(static fn() => true)
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $assert->null(
                $frame(
                    static fn() => Maybe::nothing(),
                    static fn() => Maybe::just($string),
                )
                    ->filter(static fn() => false)
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
        },
    );
};
