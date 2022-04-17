<?php
declare(strict_types = 1);

namespace Innmind\IO\Stream\Writable;

use Innmind\IO\Stream\Writable;
use Innmind\TimeContinuum\ElapsedPeriod;
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
    /** @var pure-callable(LowLevelStream, ?ElapsedPeriod): Maybe<LowLevelStream> */
    private $available;
    /** @var pure-callable(LowLevelStream, Str): Maybe<LowLevelStream> */
    private $write;
    /** @var Maybe<non-empty-string> */
    private Maybe $encoding;
    private ?ElapsedPeriod $timeout;

    /**
     * @param pure-callable(LowLevelStream, ?ElapsedPeriod): Maybe<LowLevelStream> $available
     * @param pure-callable(LowLevelStream, Str): Maybe<LowLevelStream> $write
     * @param Maybe<non-empty-string> $encoding
     */
    private function __construct(
        LowLevelStream $stream,
        callable $available,
        callable $write,
        Maybe $encoding,
        ?ElapsedPeriod $timeout,
    ) {
        $this->stream = $stream;
        $this->available = $available;
        $this->write = $write;
        $this->encoding = $encoding;
        $this->timeout = $timeout;
    }

    /**
     * @psalm-pure
     *
     * @param pure-callable(LowLevelStream, ?ElapsedPeriod): Maybe<LowLevelStream> $available
     * @param pure-callable(LowLevelStream, Str): Maybe<LowLevelStream> $write
     */
    public static function of(
        LowLevelStream $stream,
        callable $available,
        callable $write,
    ): self {
        /** @var Maybe<non-empty-string> */
        $encoding = Maybe::nothing();

        return new self($stream, $available, $write, $encoding, null);
    }

    public function toEncoding(string $encoding): self
    {
        return new self(
            $this->stream,
            $this->available,
            $this->write,
            Maybe::just($encoding),
            $this->timeout,
        );
    }

    public function timeoutAfter(ElapsedPeriod $period): self
    {
        return new self(
            $this->stream,
            $this->available,
            $this->write,
            $this->encoding,
            $period,
        );
    }

    public function write(Str $data): Maybe
    {
        $data = $this->encoding->match(
            static fn($encoding) => $data->toEncoding($encoding),
            static fn() => $data,
        );

        /** @var Maybe<Writable> */
        return ($this->available)($this->stream, $this->timeout)
            ->flatMap(fn($stream) => ($this->write)($stream, $data))
            ->map(fn($stream) => new self(
                $stream,
                $this->available,
                $this->write,
                $this->encoding,
                $this->timeout,
            ));
    }
}
