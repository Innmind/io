<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable;

use Innmind\IO\Internal\Stream as LowLevelStream;
use Innmind\Immutable\{
    Str,
    Maybe,
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
