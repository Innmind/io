<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable\Frame;

use Innmind\IO\Readable\Frame;
use Innmind\Immutable\{
    Sequence as Seq,
    Maybe,
};

/**
 * @template T
 * @implements Frame<Seq<T>>
 */
final class Sequence implements Frame
{
    /** @var Frame<T> */
    private Frame $frame;
    /** @var callable(T): bool */
    private $until;

    /**
     * @param Frame<T> $frame
     * @param callable(T): bool $until
     */
    private function __construct(Frame $frame, callable $until)
    {
        $this->frame = $frame;
        $this->until = $until;
    }

    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe {
        $values = Maybe::just([]);

        do {
            $value = ($this->frame)($read, $readLine);
            $values = $value->flatMap(
                static fn($value) => $values->map(
                    static fn($values) => \array_merge($values, [$value]),
                ),
            );
            $continue = $value
                ->exclude($this->until)
                ->match(
                    static fn() => true,
                    static fn() => false,
                );
        } while ($continue);

        return $values->map(static fn($values) => Seq::of(...$values));
    }

    /**
     * Beware, this will keep each element in the memory.
     *
     * @template A
     *
     * @param Frame<A> $frame
     *
     * @return self<A>
     */
    public static function of(Frame $frame): self
    {
        return new self($frame, static fn() => false);
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function until(callable $predicate): self
    {
        return new self($this->frame, $predicate);
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(Seq<T>): bool $predicate
     *
     * @return Frame<Seq<T>>
     */
    public function filter(callable $predicate): Frame
    {
        return Filter::of($this, $predicate);
    }

    /**
     * @psalm-mutation-free
     *
     * @template U
     *
     * @param callable(Seq<T>): U $map
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
     * @param callable(Seq<T>): Frame<U> $map
     *
     * @return Frame<U>
     */
    public function flatMap(callable $map): Frame
    {
        return FlatMap::of($this, $map);
    }
}
