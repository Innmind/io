<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable\Frame;

use Innmind\IO\Readable\Frame;
use Innmind\Immutable\Maybe;

/**
 * @template T
 * @implements Frame<T>
 */
final class Filter implements Frame
{
    /** @var Frame<T> */
    private Frame $frame;
    /** @var callable(T): bool */
    private $predicate;

    /**
     * @psalm-mutation-free
     *
     * @param Frame<T> $frame
     * @param callable(T): bool $predicate
     */
    private function __construct(Frame $frame, callable $predicate)
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
     * @param Frame<A> $frame
     * @param callable(A): bool $predicate
     *
     * @return self<A>
     */
    public static function of(Frame $frame, callable $predicate): self
    {
        return new self($frame, $predicate);
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(T): bool $predicate
     *
     * @return Frame<T>
     */
    public function filter(callable $predicate): Frame
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
     * @return Frame<U>
     */
    public function map(callable $map): Frame
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
     * @return Frame<U>
     */
    public function flatMap(callable $map): Frame
    {
        return FlatMap::of($this, $map);
    }
}
