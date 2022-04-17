<?php
declare(strict_types = 1);

namespace Innmind\IO\Stream\Writable;

use Innmind\IO\Stream\Writable;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
};

/**
 * Use this implementation for tests only
 * @psalm-immutable
 */
final class InMemory implements Writable
{
    /** @var Sequence<Str> */
    private Sequence $chunks;

    /**
     * @param Sequence<Str> $chunks
     */
    private function __construct(Sequence $chunks)
    {
        $this->chunks = $chunks;
    }

    /**
     * @psalm-pure
     */
    public static function open(): self
    {
        return new self(Sequence::of());
    }

    public function write(Str $data): Maybe
    {
        /** @var Maybe<Writable> */
        return Maybe::just(new self(($this->chunks)($data)));
    }

    /**
     * @return Sequence<Str>
     */
    public function chunks(): Sequence
    {
        return $this->chunks;
    }
}
