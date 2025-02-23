<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets\Clients\Client;

use Innmind\IO\{
    Sockets\Clients\Client\Frames\Lazy,
    Streams\Stream\Read\Frames as Stream,
};
use Innmind\Immutable\Maybe;

/**
 * @template T
 */
final class Frames
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
     * @return Maybe<T>
     */
    public function one(): Maybe
    {
        return $this->frames->one();
    }

    /**
     * @return Lazy<T>
     */
    public function lazy(): Lazy
    {
        return Lazy::of($this->frames->lazy());
    }
}
