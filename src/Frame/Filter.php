<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\IO\{
    Internal\Reader,
    Exception\RuntimeException,
};
use Innmind\Immutable\Attempt;

/**
 * @internal
 * @template T
 * @implements Implementation<T>
 */
final class Filter implements Implementation
{
    /** @var Implementation<T> */
    private Implementation $frame;
    /** @var callable(T): bool */
    private $predicate;

    /**
     * @psalm-mutation-free
     *
     * @param Implementation<T> $frame
     * @param callable(T): bool $predicate
     */
    private function __construct(Implementation $frame, callable $predicate)
    {
        $this->frame = $frame;
        $this->predicate = $predicate;
    }

    #[\Override]
    public function __invoke(Reader|Reader\Buffer $reader): Attempt
    {
        $predicate = $this->predicate;

        /** @psalm-suppress MixedArgument */
        return ($this->frame)($reader)->flatMap(
            static fn($value) => match ($predicate($value)) {
                true => Attempt::result($value),
                false => Attempt::error(new RuntimeException('Value does not match predicate')),
            },
        );
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param Implementation<A> $frame
     * @param callable(A): bool $predicate
     *
     * @return self<A>
     */
    public static function of(Implementation $frame, callable $predicate): self
    {
        return new self($frame, $predicate);
    }
}
