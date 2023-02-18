<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable;

use Innmind\Stream\Readable as LowLevelStream;
use Innmind\Immutable\{
    Fold,
    Str,
    Maybe,
    Either,
};

final class Chunks
{
    private LowLevelStream $stream;
    /** @var Maybe<string> */
    private Maybe $encoding;
    /** @var positive-int */
    private int $size;

    /**
     * @psalm-mutation-free
     *
     * @param Maybe<string> $encoding
     * @param positive-int $size
     */
    private function __construct(
        LowLevelStream $stream,
        Maybe $encoding,
        int $size,
    ) {
        $this->stream = $stream;
        $this->encoding = $encoding;
        $this->size = $size;
    }

    /**
     * @psalm-mutation-free
     *
     * @param Maybe<string> $encoding
     * @param positive-int $size
     */
    public static function of(
        LowLevelStream $stream,
        Maybe $encoding,
        int $size,
    ): self {
        return new self($stream, $encoding, $size);
    }

    /**
     * @template F
     * @template R
     * @template C
     *
     * @param Fold<F, R, C> $fold
     * @param callable(C, Str): Fold<F, R, C> $map
     *
     * @return Maybe<Either<F, R>>
     */
    public function fold(
        Fold $fold,
        callable $map,
    ): Maybe {
        $finished = Maybe::just($fold);

        do {
            /** @psalm-suppress MixedArgument Psalm lose track of the types */
            $finished = $this
                ->stream
                ->read($this->size)
                ->map(fn($chunk) => $this->encoding->match(
                    static fn($encoding) => $chunk->toEncoding($encoding),
                    static fn() => $chunk,
                ))
                ->flatMap(
                    static fn($chunk) => $finished->map(
                        static fn($fold) => $fold->flatMap(
                            static fn($compute) => $map($compute, $chunk),
                        ),
                    ),
                );

            $continue = $finished->match(
                static fn($fold) => $fold->maybe()->match(
                    static fn() => false, // failure or result so can't fold anymore
                    static fn() => true, // no failure nor result => still folding
                ),
                static fn() => false, // failed to read the stream
            );
        } while ($continue);

        return $finished->flatMap(static fn($fold) => $fold->maybe());
    }
}
