<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable;

use Innmind\Stream\Readable as LowLevelStream;
use Innmind\Immutable\{
    Str,
    Maybe,
};

final class Lines
{
    private LowLevelStream $stream;
    /** @var callable(LowLevelStream): Maybe<LowLevelStream> */
    private $ready;
    /** @var Maybe<Str\Encoding> */
    private Maybe $encoding;

    /**
     * @psalm-mutation-free
     *
     * @param callable(LowLevelStream): Maybe<LowLevelStream> $ready
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
     *
     * @param callable(LowLevelStream): Maybe<LowLevelStream> $ready
     * @param Maybe<Str\Encoding> $encoding
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
