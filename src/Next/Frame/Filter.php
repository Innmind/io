<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Frame;

use Innmind\IO\Next\Frame;
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

    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe {
        return ($this->frame)($read, $readLine)->filter(
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

    /**
     * @psalm-mutation-free
     *
     * @param callable(T): bool $predicate
     *
     * @return Implementation<T>
     */
    public function filter(callable $predicate): Implementation
    {
        return new self($this, $predicate);
    }

    /**
     * @psalm-mutation-free
     *
     * @template U
     *
     * @param callable(T): U $map
     *
     * @return Implementation<U>
     */
    public function map(callable $map): Implementation
    {
        return Map::of($this, $map);
    }

    /**
     * @psalm-mutation-free
     *
     * @template U
     *
     * @param callable(T): Frame<U> $map
     *
     * @return Implementation<U>
     */
    public function flatMap(callable $map): Implementation
    {
        return FlatMap::of($this, $map);
    }
}
