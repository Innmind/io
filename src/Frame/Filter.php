<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\IO\Internal\Reader;
use Innmind\Immutable\Maybe;

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
    public function __invoke(Reader|Reader\Buffer $reader): Maybe
    {
        return ($this->frame)($reader)->filter(
            $this->predicate,
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
