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
    private Stream\Wait|Stream\Wait\WithHeartbeat $wait;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;

    /**
     * @psalm-mutation-free
     *
     * @param Maybe<Str\Encoding> $encoding
     */
    private function __construct(
        Stream $stream,
        Stream\Wait|Stream\Wait\WithHeartbeat $wait,
        Maybe $encoding,
    ) {
        $this->stream = $stream;
        $this->wait = $wait;
        $this->encoding = $encoding;
    }

    /**
     * @psalm-mutation-free
     * @internal
     *
     * @param Maybe<Str\Encoding> $encoding
     */
    public static function of(
        Stream $stream,
        Stream\Wait|Stream\Wait\WithHeartbeat $wait,
        Maybe $encoding,
    ): self {
        return new self($stream, $wait, $encoding);
    }

    /**
     * @psalm-mutation-free
     */
    public function lazy(): Lines\Lazy
    {
        return Lines\Lazy::of(
            $this->stream,
            $this->wait,
            $this->encoding,
        );
    }
}
