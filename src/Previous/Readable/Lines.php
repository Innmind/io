<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous\Readable;

use Innmind\IO\Internal\Stream;
use Innmind\Immutable\{
    Str,
    Maybe,
};

final class Lines
{
    private Stream $stream;
    /** @var callable(Stream): Maybe<Stream> */
    private $ready;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;

    /**
     * @psalm-mutation-free
     *
     * @param callable(Stream): Maybe<Stream> $ready
     * @param Maybe<Str\Encoding> $encoding
     */
    private function __construct(
        Stream $stream,
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
     *
     * @param callable(Stream): Maybe<Stream> $ready
     * @param Maybe<Str\Encoding> $encoding
     */
    public static function of(
        Stream $stream,
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
