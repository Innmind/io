<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\IO\Frame;
use Innmind\Immutable\{
    Sequence as Seq,
    Maybe,
};

/**
 * @template T
 * @implements Implementation<Seq<Maybe<T>>>
 */
final class Sequence implements Implementation
{
    /**
     * @psalm-mutation-free
     *
     * @param Frame<T> $frame
     */
    private function __construct(
        private Frame $frame,
    ) {
    }

    #[\Override]
    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe {
        $frame = $this->frame;
        $frames = Seq::lazy(static function() use ($read, $readLine, $frame) {
            while (true) {
                yield $frame($read, $readLine);
            }
        });

        return Maybe::just($frames);
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param Frame<A> $frame
     *
     * @return self<A>
     */
    public static function of(Frame $frame): self
    {
        return new self($frame);
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(Seq<Maybe<T>>): bool $predicate
     *
     * @return Implementation<Seq<Maybe<T>>>
     */
    #[\Override]
    public function filter(callable $predicate): Implementation
    {
        return Filter::of($this, $predicate);
    }

    /**
     * @psalm-mutation-free
     *
     * @template U
     *
     * @param callable(Seq<Maybe<T>>): U $map
     *
     * @return Implementation<U>
     */
    #[\Override]
    public function map(callable $map): Implementation
    {
        return Map::of($this, $map);
    }

    /**
     * @psalm-mutation-free
     *
     * @template U
     *
     * @param callable(Seq<Maybe<T>>): Frame<U> $map
     *
     * @return Implementation<U>
     */
    #[\Override]
    public function flatMap(callable $map): Implementation
    {
        return FlatMap::of($this, $map);
    }
}
