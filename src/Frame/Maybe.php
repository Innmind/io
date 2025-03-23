<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\IO\{
    Frame,
    Internal\Reader,
};
use Innmind\Immutable\Maybe as Monad;

/**
 * Use this frame to hardcode a value inside a frame composition
 *
 * @internal
 * @template T
 * @implements Implementation<T>
 */
final class Maybe implements Implementation
{
    /**
     * @psalm-mutation-free
     *
     * @param Monad<T> $value
     */
    private function __construct(
        private Monad $value,
    ) {
    }

    #[\Override]
    public function __invoke(Reader|Reader\Buffer $reader): Monad
    {
        return $this->value;
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param A $value
     *
     * @return self<A>
     */
    public static function just(mixed $value): self
    {
        return new self(Monad::just($value));
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param Monad<A> $value
     *
     * @return self<A>
     */
    public static function of(Monad $value): self
    {
        return new self($value);
    }
}
