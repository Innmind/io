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
}
