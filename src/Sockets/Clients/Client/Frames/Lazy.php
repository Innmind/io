<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets\Clients\Client\Frames;

use Innmind\IO\Streams\Stream\Read\Frames\Lazy as Stream;
use Innmind\Immutable\Sequence;

/**
 * @template T
 */
final class Lazy
{
    /**
     * @param Stream<T> $frames
     */
    private function __construct(
        private Stream $frames,
    ) {
    }

    /**
     * @internal
     * @template A
     *
     * @param Stream<A> $frames
     *
     * @return self<A>
     */
    public static function of(Stream $frames): self
    {
        return new self($frames);
    }

    /**
     * @return Sequence<T>
     */
    public function sequence(): Sequence
    {
        return $this->frames->sequence();
    }
}
