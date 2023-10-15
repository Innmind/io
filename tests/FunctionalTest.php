<?php
declare(strict_types = 1);

namespace Tests\Innmind\IO;

use Innmind\IO\IO;
use Innmind\Stream\{
    Readable\Stream,
    Watch\Select,
};
use Innmind\Immutable\{
    Fold,
    Str,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class FunctionalTest extends TestCase
{
    use BlackBox;

    public function testReadChunks()
    {
        $this
            ->forAll(Set\Elements::of(
                [1, 'z', ['f', 'o', 'o', 'b', 'a', 'r', 'b', 'a', 'z']],
                [2, 'z', ['fo', 'ob', 'ar', 'ba', 'z']],
                [3, 'baz', ['foo', 'bar', 'baz']],
            ))
            ->then(function($in) {
                [$size, $quit, $expected] = $in;

                $stream = Stream::ofContent('foobarbaz');
                $chunks = IO::of(Select::waitForever(...))
                    ->readable()
                    ->wrap($stream)
                    ->watch()
                    ->chunks($size)
                    ->fold(
                        Fold::with([]),
                        static fn($chunks, $chunk) => match ($chunk->toString()) {
                            $quit => Fold::result(\array_merge($chunks, [$chunk->toString()])),
                            default => Fold::with(\array_merge($chunks, [$chunk->toString()])),
                        },
                    )
                    ->flatMap(static fn($result) => $result->maybe())
                    ->match(
                        static fn($chunks) => $chunks,
                        static fn() => null,
                    );

                $this->assertSame($expected, $chunks);
            });
    }

    public function testReadChunksEncoding()
    {
        $stream = Stream::ofContent('foob');
        $chunks = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap($stream)
            ->toEncoding(Str\Encoding::ascii)
            ->watch()
            ->chunks(1)
            ->fold(
                Fold::with([]),
                static fn($chunks, $chunk) => match ($chunk->toString()) {
                    'b' => Fold::result(\array_merge($chunks, [$chunk->encoding()->toString()])),
                    default => Fold::with(\array_merge($chunks, [$chunk->encoding()->toString()])),
                },
            )
            ->flatMap(static fn($result) => $result->maybe())
            ->match(
                static fn($chunks) => $chunks,
                static fn() => null,
            );

        $this->assertSame(
            ['ASCII', 'ASCII', 'ASCII', 'ASCII'],
            $chunks,
        );
    }

    public function testReadChunksWithALazySequence()
    {
        $this
            ->forAll(
                Set\Elements::of(
                    [1, 'foobarbaz', ['f', 'o', 'o', 'b', 'a', 'r', 'b', 'a', 'z', '']],
                    [2, 'foobarbaz', ['fo', 'ob', 'ar', 'ba', 'z']],
                    [3, 'foobarbaz', ['foo', 'bar', 'baz', '']],
                    [1, '', ['']],
                    [1, "\n", ["\n", '']],
                ),
                Set\Elements::of(Str\Encoding::ascii, Str\Encoding::utf8),
            )
            ->then(function($in, $encoding) {
                [$size, $content, $expected] = $in;

                $stream = Stream::ofContent($content);
                $chunks = IO::of(Select::waitForever(...))
                    ->readable()
                    ->wrap($stream)
                    ->toEncoding($encoding)
                    ->watch()
                    ->chunks($size)
                    ->lazy()
                    ->rewindable()
                    ->sequence();
                $values = $chunks
                    ->map(static fn($chunk) => $chunk->toString())
                    ->toList();
                $encodings = $chunks
                    ->map(static fn($chunk) => $chunk->encoding()->toString())
                    ->distinct()
                    ->toList();

                $this->assertSame($expected, $values);
                $this->assertSame([$encoding->toString()], $encodings);
                $this->assertSame(0, $stream->position()->toInt());
                $this->assertFalse($stream->end());
            });
    }

    public function testReadChunksWithANonRewindableLazySequence()
    {
        $this
            ->forAll(
                Set\Elements::of(
                    [1, 'foobarbaz', ['f', 'o', 'o', 'b', 'a', 'r', 'b', 'a', 'z', '']],
                    [2, 'foobarbaz', ['fo', 'ob', 'ar', 'ba', 'z']],
                    [3, 'foobarbaz', ['foo', 'bar', 'baz', '']],
                    [1, '', ['']],
                    [1, "\n", ["\n", '']],
                ),
                Set\Elements::of(Str\Encoding::ascii, Str\Encoding::utf8),
            )
            ->then(function($in, $encoding) {
                [$size, $content, $expected] = $in;

                $stream = Stream::ofContent($content);
                $chunks = IO::of(Select::waitForever(...))
                    ->readable()
                    ->wrap($stream)
                    ->toEncoding($encoding)
                    ->watch()
                    ->chunks($size)
                    ->lazy()
                    ->sequence();
                $values = $chunks
                    ->map(static fn($chunk) => $chunk->toString())
                    ->toList();
                $encodings = $chunks
                    ->map(static fn($chunk) => $chunk->encoding()->toString())
                    ->distinct()
                    ->toList();

                $this->assertSame($expected, $values);
                $this->assertSame([$encoding->toString()], $encodings);
                $this->assertTrue($stream->end());
            });
    }
}
