<?php
declare(strict_types = 1);

namespace Innmind\IO\Stream\Writable;

use Innmind\IO\Stream\Writable;
use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
    SideEffect,
};

/**
 * Use this implementation for tests only
 * @psalm-immutable
 */
final class InMemory implements Writable
{
    /** @var Sequence<Str> */
    private Sequence $chunks;
    /** @var Maybe<non-empty-string> */
    private Maybe $encoding;

    /**
     * @param Sequence<Str> $chunks
     * @param Maybe<non-empty-string> $encoding
     */
    private function __construct(Sequence $chunks, Maybe $encoding)
    {
        $this->chunks = $chunks;
        $this->encoding = $encoding;
    }

    /**
     * @psalm-pure
     */
    public static function open(): self
    {
        /** @var Maybe<non-empty-string> */
        $encoding = Maybe::nothing();

        return new self(Sequence::of(), $encoding);
    }

    public function toEncoding(string $encoding): self
    {
        return new self($this->chunks, Maybe::just($encoding));
    }

    public function timeoutAfter(ElapsedPeriod $period): self
    {
        return $this;
    }

    public function write(Str $data): Maybe
    {
        $data = $this->encoding->match(
            static fn($encoding) => $data->toEncoding($encoding),
            static fn() => $data,
        );

        /** @var Maybe<Writable> */
        return Maybe::just(new self(($this->chunks)($data), $this->encoding));
    }

    public function terminate(): SideEffect
    {
        return new SideEffect;
    }

    /**
     * @return Sequence<Str>
     */
    public function chunks(): Sequence
    {
        return $this->chunks;
    }
}
