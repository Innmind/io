<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\IO\{
    Frame,
    Internal\Reader,
};
use Innmind\Immutable\Attempt;

/**
 * @internal
 * @template T
 * @template U
 * @implements Implementation<U>
 */
final class FlatMap implements Implementation
{
    /**
     * @psalm-mutation-free
     *
     * @param Implementation<T> $frame
     * @param \Closure(T): Frame<U> $map
     */
    private function __construct(
        private Implementation $frame,
        private \Closure $map,
    ) {
    }

    #[\Override]
    public function __invoke(Reader|Reader\Buffer $reader): Attempt
    {
        $map = $this->map;

        /** @psalm-suppress MixedArgument */
        return ($this->frame)($reader)->flatMap(
            static fn($value) => $map($value)($reader),
        );
    }

    /**
     * @psalm-pure
     * @template A
     * @template B
     *
     * @param Implementation<A> $frame
     * @param callable(A): Frame<B> $map
     *
     * @return self<A, B>
     */
    public static function of(Implementation $frame, callable $map): self
    {
        return new self($frame, \Closure::fromCallable($map));
    }
}
