<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Readable;

use Innmind\IO\Internal\Stream;
use Innmind\Immutable\{
    Str,
    Maybe,
};

final class Chunks
{
    private Stream $stream;
    /** @var callable(Stream): Maybe<Stream> */
    private $ready;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;
    /** @var positive-int */
    private int $size;

    /**
     * @psalm-mutation-free
     *
     * @param callable(Stream): Maybe<Stream> $ready
     * @param Maybe<Str\Encoding> $encoding
     * @param positive-int $size
     */
    private function __construct(
        Stream $stream,
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
     *
     * @param callable(Stream): Maybe<Stream> $ready
     * @param Maybe<Str\Encoding> $encoding
     * @param positive-int $size
     */
    public static function of(
        Stream $stream,
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
