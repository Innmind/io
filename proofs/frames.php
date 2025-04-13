<?php
declare(strict_types = 1);

use Innmind\IO\{
    Frame,
    Internal\Reader,
    Internal\Stream,
    Internal\Stream\Wait,
    Internal\Watch,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
};
use Innmind\BlackBox\Set;

return static function() {
    $reader = static function(Str $data) {
        $tmp = \tmpfile();
        \fwrite($tmp, $data->toString());
        \fseek($tmp, 0);

        return Reader::of(
            Wait::of(Watch::new(), Stream::of($tmp)),
            Maybe::just($data->encoding()),
        );
    };

    yield proof(
        'Frame::just()',
        given(
            Set::type(),
            Set::strings()
                ->unicode()
                ->map(Str::of(...)),
        ),
        static function($assert, $value, $read) use ($reader) {
            $frame = Frame::just($value);

            $assert->same(
                $value,
                $frame($reader($read))->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
        },
    );

    yield proof(
        'Frame::maybe()',
        given(
            Set::type(),
            Set::strings()
                ->unicode()
                ->map(Str::of(...)),
        ),
        static function($assert, $value, $read) use ($reader) {
            $frame = Frame::maybe(Maybe::just($value));

            $assert->same(
                $value,
                $frame($reader($read))->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
        },
    );

    yield proof(
        'Frame::maybe() nothing',
        given(
            Set::strings()
                ->unicode()
                ->map(Str::of(...)),
        ),
        static function($assert, $read) use ($reader) {
            $frame = Frame::maybe(Maybe::nothing());

            $assert->null(
                $frame($reader($read))->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
        },
    );

    yield proof(
        'Frame::chunk()',
        given(
            Set::strings()
                ->unicode()
                ->map(Str::of(...))
                ->map(static fn($str) => $str->toEncoding(Str\Encoding::ascii)),
        ),
        static function($assert, $string) use ($reader) {
            $size = $string->length();
            $frame = Frame::chunk($size)->loose();

            $assert->same(
                $string->toString(),
                $frame($reader($string))->match(
                    static fn($value) => $value->toString(),
                    static fn() => null,
                ),
            );
        },
    );

    yield proof(
        'Frame::line()',
        given(
            Set::strings()
                ->unicode()
                ->filter(static fn($string) => !\str_contains($string, "\n"))
                ->map(Str::of(...)),
        ),
        static function($assert, $string) use ($reader) {
            $frame = Frame::line();

            $assert->same(
                $string->toString(),
                $frame($reader($string))->match(
                    static fn($value) => $value->toString(),
                    static fn() => null,
                ),
            );
        },
    );

    yield proof(
        'Frame::sequence()',
        given(Set::sequence(
            Set::strings()
                ->unicode()
                ->atLeast(1)
                ->filter(static fn($string) => !\str_contains($string, "\n")),
        )),
        static function($assert, $lines) use ($reader) {
            $frame = Frame::sequence(Frame::line());
            $data = \implode("\n", $lines);

            $frame = $frame
                ->map(
                    static fn($lines) => $lines
                        ->takeWhile(static fn($line) => $line->match(
                            static fn($line) => !$line->empty(),
                            static fn() => false,
                        ))
                        ->sink(Sequence::of())
                        ->maybe(static fn($lines, $line) => $line->maybe()->map($lines)),
                )
                ->flatMap(Frame::maybe(...));
            $values = $frame($reader(Str::of($data)))->match(
                static fn($values) => $values,
                static fn() => null,
            );

            $assert
                ->object($values)
                ->instance(Sequence::class, 'Frame::sequence() must return a lazy Sequence');
            $assert->same(
                $lines,
                $values
                    ->map(static fn($line) => $line->rightTrim("\n")->toString())
                    ->toList(),
            );
        },
    );

    yield proof(
        'Frame::filter()',
        given(
            Set::strings()
                ->unicode()
                ->map(Str::of(...))
                ->map(static fn($string) => $string->toEncoding(Str\Encoding::ascii)),
        ),
        static function($assert, $string) use ($reader) {
            $frame = Frame::chunk($string->length())->strict();

            $assert->same(
                $string->toString(),
                $frame
                    ->filter(static fn() => true)($reader($string))
                    ->match(
                        static fn($value) => $value->toString(),
                        static fn() => null,
                    ),
            );
            $assert->null(
                $frame
                    ->filter(static fn() => false)($reader($string))
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
        },
    );

    yield proof(
        'Frame::buffer()',
        given(
            Set::strings()
                ->unicode()
                ->atLeast(1)
                ->map(Str::of(...))
                ->map(static fn($string) => $string->toEncoding(Str\Encoding::ascii)),
            Set::strings()
                ->unicode()
                ->map(Str::of(...))
                ->map(static fn($string) => $string->toEncoding(Str\Encoding::ascii)),
            Set::integers()->between(1, 100),
        ),
        static function($assert, $a, $b, $size) use ($reader) {
            $frame = Frame::buffer(
                $a->length(),
                Frame::chunk($size)->loose(),
            )->flatMap(
                static fn($value) => Frame::chunk($b->length())
                    ->strict()
                    ->map(static fn($rest) => $value->append($rest)),
            );

            $assert->same(
                $a->take($size)->append($b)->toString(),
                $frame($reader($a->append($b)))
                    ->match(
                        static fn($value) => $value->toString(),
                        static fn() => null,
                    ),
            );
        },
    );

    yield proof(
        'Frame::buffer() fails when not used with a fixed size frame',
        given(
            Set::strings()
                ->unicode()
                ->atLeast(1)
                ->map(Str::of(...))
                ->map(static fn($string) => $string->toEncoding(Str\Encoding::ascii)),
        ),
        static function($assert, $a) use ($reader) {
            $frame = Frame::buffer(
                $a->length(),
                Frame::line(),
            );

            $assert->throws(
                static fn() => $frame($reader($a)),
            );
        },
    );

    yield proof(
        'Frame::compose()',
        given(
            Set::sequence(Set::strings())->atLeast(1),
        ),
        static function($assert, $chunks) use ($reader) {
            $frame = Frame::compose(
                static fn(...$chunks) => $chunks,
                ...\array_map(
                    static fn($chunk) => Frame::chunk(Str::of($chunk)->toEncoding(Str\Encoding::ascii)->length())->strict(),
                    $chunks,
                ),
            );

            $assert->same(
                $chunks,
                $frame($reader(Str::of(\implode('', $chunks))->toEncoding(Str\Encoding::ascii)))->match(
                    static fn($chunks) => \array_map(
                        static fn($chunk) => $chunk->toString(),
                        $chunks,
                    ),
                    static fn($e) => $e,
                ),
            );
        },
    );
};
