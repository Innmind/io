<?php
declare(strict_types = 1);

use Innmind\IO\{
    IO,
    Frame,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    SideEffect,
    Monoid\Concat,
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

    yield test(
        'IO::streams()->acquire()->read()->frames()->one()',
        static function($assert) {
            $http = <<<RAW
            POST /some-form HTTP/1.1\r
            User-Agent: Mozilla/4.0 (compatible; MSIE5.01; Windows NT)\r
            Host: innmind.com\r
            Content-Type: application/x-www-form-urlencoded\r
            Content-Length: 23\r
            Accept-Language: fr-fr\r
            Accept-Encoding: gzip, deflate\r
            Connection: Keep-Alive\r
            \r
            some[key]=value&foo=bar\r
            \r

            RAW;
            $tmp = \tmpfile();
            \fwrite($tmp, $http);

            $firstLine = Frame::line()->map(static fn($line) => $line->trim()->toString());
            $headersAndBody = Frame::sequence(
                Frame::line()->map(static fn($line) => $line->trim()),
            )
                ->map(
                    static fn($lines) => $lines
                        ->map(static fn($line) => $line->match(
                            static fn($line) => $line,
                            static fn() => throw new Exception,
                        ))
                        ->takeWhile(static fn($line) => !$line->empty())
                        ->memoize(),
                )
                ->flatMap(
                    static fn($headers) => Frame::chunk(
                        (int) $headers
                            ->toList()[3]
                            ->takeEnd(2)
                            ->toString(),
                    )
                        ->strict()
                        ->map(static fn($body) => [
                            $headers
                                ->filter(static fn($header) => !$header->empty())
                                ->map(static fn($header) => $header->toString())
                                ->toList(),
                            $body->toString(),
                        ]),
                );

            $request = IO::fromAmbientAuthority()
                ->streams()
                ->acquire($tmp)
                ->read()
                ->toEncoding(Str\Encoding::ascii)
                ->watch()
                ->frames(
                    $firstLine->flatMap(
                        static fn($firstLine) => $headersAndBody->map(
                            static fn($headersAndBody) => [$firstLine, ...$headersAndBody],
                        ),
                    ),
                )
                ->one()
                ->match(
                    static fn($request) => $request,
                    static fn() => null,
                );

            $assert->not()->null($request);
            $assert->same('POST /some-form HTTP/1.1', $request[0]);
            $assert->same(
                [
                    'User-Agent: Mozilla/4.0 (compatible; MSIE5.01; Windows NT)',
                    'Host: innmind.com',
                    'Content-Type: application/x-www-form-urlencoded',
                    'Content-Length: 23',
                    'Accept-Language: fr-fr',
                    'Accept-Encoding: gzip, deflate',
                    'Connection: Keep-Alive',
                ],
                $request[1],
            );
            $assert->same('some[key]=value&foo=bar', $request[2]);
        },
    );

    yield proof(
        'IO::streams()->acquire()->read()->frames()->sequence()',
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
            $tmp = \tmpfile();
            \fwrite($tmp, \implode("\n", $lines));

            $load = static fn() => IO::fromAmbientAuthority()
                ->streams()
                ->acquire($tmp)
                ->read()
                ->toEncoding(Str\Encoding::ascii)
                ->watch()
                ->frames(Frame::line())
                ->lazy()
                ->sequence();

            $sequence = $load();
            $assert->same(
                \count($lines) ?: 1, // by default it always read an empty string
                $sequence->size(),
            );
            $assert->same(
                [],
                $sequence->toList(),
                'Stream should not be rewinde',
            );

            \fseek($tmp, 0); // rewind
            $assert->same(
                \implode("\n", $lines),
                $load()
                    ->fold(new Concat)
                    ->toString(),
            );
        },
    );

    yield proof(
        'IO::streams()->acquire()->read()->frames()->rewindable()->sequence()',
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
            $tmp = \tmpfile();
            \fwrite($tmp, \implode("\n", $lines));

            $sequence = IO::fromAmbientAuthority()
                ->streams()
                ->acquire($tmp)
                ->read()
                ->toEncoding(Str\Encoding::ascii)
                ->watch()
                ->frames(Frame::line())
                ->lazy()
                ->rewindable()
                ->sequence();

            $assert->same(
                \count($lines) ?: 1, // by default it always read an empty string
                $sequence->size(),
            );

            $assert->same(
                \implode("\n", $lines),
                $sequence
                    ->fold(new Concat)
                    ->toString(),
            );
        },
    );

    yield proof(
        'IO::streams()->acquire()->read()->pool()->chunks()',
        given(
            $string,
            $string,
            Set\Elements::of(...Str\Encoding::cases()),
        ),
        static function($assert, $a, $b, $encoding) {
            $tmpA = \tmpfile();
            \fwrite($tmpA, $a);
            $tmpB = \tmpfile();
            \fwrite($tmpB, $b);

            $io = IO::fromAmbientAuthority();
            $chunks = $io
                ->streams()
                ->acquire($tmpA)
                ->read()
                ->pool('a')
                ->with(
                    'b',
                    $io
                        ->streams()
                        ->acquire($tmpB)
                        ->read(),
                )
                ->watch()
                ->toEncoding($encoding)
                ->chunks();

            $assert->same(2, $chunks->size());
            $chunks->foreach(static fn($chunk) => $assert->same(
                $encoding,
                $chunk->value()->encoding(),
            ));
            $assert->same(
                [$a],
                $chunks
                    ->filter(static fn($chunk) => $chunk->key() === 'a')
                    ->map(static fn($chunk) => $chunk->value()->toString())
                    ->toList(),
            );
            $assert->same(
                [$b],
                $chunks
                    ->filter(static fn($chunk) => $chunk->key() === 'b')
                    ->map(static fn($chunk) => $chunk->value()->toString())
                    ->toList(),
            );
        },
    );

    yield proof(
        'IO::streams()->acquire()->close()',
        given($string),
        static function($assert, $content) {
            $tmp = \tmpfile();
            \fwrite($tmp, $content);

            $stream = IO::fromAmbientAuthority()
                ->streams()
                ->acquire($tmp);

            $assert->true($stream->close()->match(
                static fn() => true,
                static fn() => false,
            ));
            $assert->true(
                $stream->close()->match(
                    static fn() => true,
                    static fn() => false,
                ),
                'Closing an already closed stream should not fail',
            );

            $assert->same(
                [],
                $stream
                    ->read()
                    ->frames(Frame::chunk(1)->strict())
                    ->lazy()
                    ->rewindable()
                    ->sequence()
                    ->toList(),
            );
        },
    );

    yield proof(
        'IO::streams()->acquire()->write()->sink()',
        given(
            $strings,
            Set\Elements::of(...Str\Encoding::cases()),
        ),
        static function($assert, $chunks, $encoding) {
            $tmp = \tmpfile();

            $sideEffect = IO::fromAmbientAuthority()
                ->streams()
                ->acquire($tmp)
                ->write()
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
            \fseek($tmp, 0);
            $assert->same(
                \implode('', $chunks),
                \stream_get_contents($tmp),
            );
        },
    );
};
