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
    private Stream\Wait|Stream\Wait\WithHeartbeat $wait;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;
    /** @var positive-int */
    private int $size;

    /**
     * @psalm-mutation-free
     *
     * @param Maybe<Str\Encoding> $encoding
     * @param positive-int $size
     */
    private function __construct(
        Stream $stream,
        Stream\Wait|Stream\Wait\WithHeartbeat $wait,
        Maybe $encoding,
        int $size,
    ) {
        $this->stream = $stream;
        $this->wait = $wait;
        $this->encoding = $encoding;
        $this->size = $size;
    }

    /**
     * @psalm-mutation-free
     * @internal
     *
     * @param Maybe<Str\Encoding> $encoding
     * @param positive-int $size
     */
    public static function of(
        Stream $stream,
        Stream\Wait|Stream\Wait\WithHeartbeat $wait,
        Maybe $encoding,
        int $size,
    ): self {
        return new self($stream, $wait, $encoding, $size);
    }

    /**
     * @psalm-mutation-free
     */
    public function lazy(): Chunks\Lazy
    {
        return Chunks\Lazy::of(
            $this->stream,
            $this->wait,
            $this->encoding,
            $this->size,
        );
    }
}
