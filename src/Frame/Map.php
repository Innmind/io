<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\IO\Internal\Reader;
use Innmind\Immutable\Maybe;

/**
 * @internal
 * @template T
 * @template U
 * @implements Implementation<U>
 */
final class Map implements Implementation
{
    /**
     * @psalm-mutation-free
     *
     * @param Implementation<T> $frame
     * @param \Closure(T): U $map
     */
    private function __construct(
        private Implementation $frame,
        private \Closure $map,
    ) {
    }

    #[\Override]
    public function __invoke(Reader|Reader\Buffer $reader): Maybe
    {
        return ($this->frame)($reader)->map(
            $this->map,
        );
    }

    /**
     * @psalm-pure
     * @template A
     * @template B
     *
     * @param Implementation<A> $frame
     * @param callable(A): B $map
     *
     * @return self<A, B>
     */
    public static function of(Implementation $frame, callable $map): self
    {
        return new self($frame, \Closure::fromCallable($map));
    }
}
