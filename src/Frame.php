<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\IO\{
    Frame\Implementation,
    Frame\Maybe as M,
    Frame\Line,
    Frame\Sequence,
    Frame\Buffer,
    Internal\Reader,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Attempt,
    Sequence as Seq,
};

/**
 * @template-covariant T
 */
final class Frame
{
    /**
     * @psalm-mutation-free
     *
     * @param Implementation<T> $implementation
     */
    private function __construct(
        private Implementation $implementation,
    ) {
    }

    /**
     * @internal
     *
     * @return Attempt<T>
     */
    public function __invoke(Reader|Reader\Buffer $reader): Attempt
    {
        return ($this->implementation)($reader);
    }

    /**
     * @psalm-pure
     * @template U
     *
     * @param U $value
     *
     * @return self<U>
     */
    public static function just(mixed $value): self
    {
        return new self(M::just($value));
    }

    /**
     * @psalm-pure
     * @template U
     *
     * @param Maybe<U> $value
     *
     * @return self<U>
     */
    public static function maybe(Maybe $value): self
    {
        return new self(M::of($value));
    }

    /**
     * @psalm-pure
     *
     * @param int<1, max> $size
     */
    public static function chunk(int $size): Frame\Provider\Chunk
    {
        return Frame\Provider\Chunk::of(
            static fn(Implementation $implementation) => new self($implementation),
            $size,
        );
    }

    /**
     * @psalm-pure
     *
     * @return self<Str>
     */
    public static function line(): self
    {
        return new self(Line::new());
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param callable(...mixed): A $map
     *
     * @return self<A>
     */
    public static function compose(
        callable $map,
        self $first,
        self ...$rest,
    ): self {
        return \array_reduce(
            $rest,
            static fn(self $carry, self $frame) => $carry->flatMap(
                static fn(array $args) => $frame->map(
                    static fn($value) => \array_merge($args, [$value]),
                ),
            ),
            $first->map(static fn($value) => [$value]),
        )->map(static fn(array $args) => $map(...$args));
    }

    /**
     * Beware, this produces a lazy Sequence so when you compose many of them
     * the order of operations may not be the one you expect.
     *
     * @psalm-pure
     * @template U
     *
     * @param self<U> $frame
     *
     * @return self<Seq<Attempt<U>>>
     */
    public static function sequence(self $frame): self
    {
        return new self(Sequence::of($frame));
    }

    /**
     * Use this method to put the stream data in a buffer.
     *
     * This is useful to avoid accessing the stream, and potentially watching if
     * it's readable, at each frame composition. This can reduce latency.
     *
     * Be careful when using this frame as the buffer is only accessible to the
     * provided frame.
     *
     * @psalm-pure
     * @template U
     *
     * @param int<1, max> $size
     * @param self<U> $frame
     *
     * @return self<U>
     */
    public static function buffer(int $size, self $frame): self
    {
        /** @psalm-suppress ImpurePropertyFetch It's safe to access the implementation */
        return new self(Buffer::of($size, $frame->implementation));
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        return new self(Frame\Filter::of(
            $this->implementation,
            $predicate,
        ));
    }

    /**
     * @psalm-mutation-free
     *
     * @template U
     *
     * @param callable(T): U $map
     *
     * @return self<U>
     */
    public function map(callable $map): self
    {
        return new self(Frame\Map::of(
            $this->implementation,
            $map,
        ));
    }

    /**
     * @psalm-mutation-free
     *
     * @template U
     *
     * @param callable(T): self<U> $map
     *
     * @return self<U>
     */
    public function flatMap(callable $map): self
    {
        return new self(Frame\FlatMap::of(
            $this->implementation,
            $map,
        ));
    }
}
