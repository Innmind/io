<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable;

use Innmind\IO\Internal\Stream\Stream as LowLevelStream;
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @template-covariant T of LowLevelStream
 */
final class Lines
{
    /** @var T */
    private LowLevelStream $stream;
    /** @var callable(T): Maybe<T> */
    private $ready;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;

    /**
     * @psalm-mutation-free
     *
     * @param T $stream
     * @param callable(T): Maybe<T> $ready
     * @param Maybe<Str\Encoding> $encoding
     */
    private function __construct(
        LowLevelStream $stream,
        callable $ready,
        Maybe $encoding,
    ) {
        $this->stream = $stream;
        $this->ready = $ready;
        $this->encoding = $encoding;
    }

    /**
     * @psalm-mutation-free
     * @internal
     * @template A of LowLevelStream
     *
     * @param A $stream
     * @param callable(A): Maybe<A> $ready
     * @param Maybe<Str\Encoding> $encoding
     *
     * @return self<A>
     */
    public static function of(
        LowLevelStream $stream,
        callable $ready,
        Maybe $encoding,
    ): self {
        return new self($stream, $ready, $encoding);
    }

    /**
     * @psalm-mutation-free
     */
    public function lazy(): Lines\Lazy
    {
        return Lines\Lazy::of(
            $this->stream,
            $this->ready,
            $this->encoding,
        );
    }
}
