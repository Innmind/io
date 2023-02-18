<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable;

use Innmind\Stream\Readable as LowLevelStream;
use Innmind\Immutable\Maybe;

final class Stream
{
    private LowLevelStream $stream;
    /** @var Maybe<string> */
    private Maybe $encoding;

    /**
     * @psalm-mutation-free
     *
     * @param Maybe<string> $encoding
     */
    private function __construct(
        LowLevelStream $stream,
        Maybe $encoding,
    ) {
        $this->stream = $stream;
        $this->encoding = $encoding;
    }

    /**
     * @psalm-mutation-free
     */
    public static function of(LowLevelStream $stream): self
    {
        /** @var Maybe<string> */
        $encoding = Maybe::nothing();

        return new self($stream, $encoding);
    }

    /**
     * @psalm-mutation-free
     */
    public function toEncoding(string $encoding): self
    {
        return new self($this->stream, Maybe::just($encoding));
    }

    /**
     * @psalm-mutation-free
     *
     * @param positive-int $size
     */
    public function chunks(int $size): Chunks
    {
        return Chunks::of($this->stream, $this->encoding, $size);
    }
}
