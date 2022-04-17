<?php
declare(strict_types = 1);

namespace Innmind\IO\Stream\Writable;

use Innmind\IO\Stream\Writable;
use Innmind\Stream\Writable as LowLevelStream;
use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Stream implements Writable
{
    private LowLevelStream $stream;
    /** @var pure-callable(LowLevelStream): Maybe<LowLevelStream> */
    private $available;
    /** @var pure-callable(LowLevelStream, Str): Maybe<LowLevelStream> */
    private $write;

    /**
     * @param pure-callable(LowLevelStream): Maybe<LowLevelStream> $available
     * @param pure-callable(LowLevelStream, Str): Maybe<LowLevelStream> $write
     */
    private function __construct(
        LowLevelStream $stream,
        callable $available,
        callable $write,
    ) {
        $this->stream = $stream;
        $this->available = $available;
        $this->write = $write;
    }

    /**
     * @param pure-callable(LowLevelStream): Maybe<LowLevelStream> $available
     * @param pure-callable(LowLevelStream, Str): Maybe<LowLevelStream> $write
     */
    public static function of(
        LowLevelStream $stream,
        callable $available,
        callable $write,
    ): self {
        return new self($stream, $available, $write);
    }

    public function write(Str $data): Maybe
    {
        /** @var Maybe<Writable> */
        return ($this->available)($this->stream)
            ->flatMap(fn($stream) => ($this->write)($stream, $data))
            ->map(fn($stream) => new self($stream, $this->available, $this->write));
    }
}
