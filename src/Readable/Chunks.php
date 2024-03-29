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

/**
 * @template-covariant T of LowLevelStream
 */
final class Chunks
{
    /** @var T */
    private LowLevelStream $stream;
    /** @var callable(T): Maybe<T> */
    private $ready;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;
    /** @var positive-int */
    private int $size;

    /**
     * @psalm-mutation-free
     *
     * @param T $stream
     * @param callable(T): Maybe<T> $ready
     * @param Maybe<Str\Encoding> $encoding
     * @param positive-int $size
     */
    private function __construct(
        LowLevelStream $stream,
        callable $ready,
        Maybe $encoding,
        int $size,
    ) {
        $this->stream = $stream;
        $this->ready = $ready;
        $this->encoding = $encoding;
        $this->size = $size;
    }

    /**
     * @psalm-mutation-free
     * @internal
     * @template A of LowLevelStream
     *
     * @param A $stream
     * @param callable(A): Maybe<A> $ready
     * @param Maybe<Str\Encoding> $encoding
     * @param positive-int $size
     *
     * @return self<A>
     */
    public static function of(
        LowLevelStream $stream,
        callable $ready,
        Maybe $encoding,
        int $size,
    ): self {
        return new self($stream, $ready, $encoding, $size);
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
            $finished = ($this->ready)($this->stream)
                ->flatMap(fn($stream) => $stream->read($this->size))
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

    /**
     * @psalm-mutation-free
     */
    public function lazy(): Chunks\Lazy
    {
        return Chunks\Lazy::of(
            $this->stream,
            $this->ready,
            $this->encoding,
            $this->size,
        );
    }
}
