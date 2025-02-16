<?php
declare(strict_types = 1);

namespace Innmind\IO\Next;

use Innmind\IO\{
    Next\Frame\Implementation,
    Next\Frame\Maybe as M,
    Next\Frame\Chunk,
    Next\Frame\Line,
    Next\Frame\Sequence,
    Readable\Frame as Old,
};
use Innmind\Immutable\{
    Str,
    Maybe,
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
     * @param callable(?int<1, max>): Maybe<Str> $read
     * @param callable(): Maybe<Str> $readLine
     *
     * @return Maybe<T>
     */
    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe {
        return ($this->implementation)($read, $readLine);
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
     *
     * @return self<Str>
     */
    public static function chunk(int $size): self
    {
        return new self(Chunk::of($size));
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
     * @template U
     *
     * @param self<U> $frame
     *
     * @return self<Seq<Maybe<U>>>
     */
    public static function sequence(self $frame): self
    {
        return new self(Sequence::of($frame));
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
        return new self($this->implementation->filter($predicate));
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
        return new self($this->implementation->map($map));
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
        return new self($this->implementation->flatMap($map));
    }

    /**
     * @psalm-suppress all
     * @todo delete when switching between old and new implementation of IO
     *
     * @return Old<T>
     */
    public function toOld(): Old
    {
        return new class($this) implements Old {
            public function __construct(
                private namespace\Frame $self,
            ) {
            }

            public function __invoke(
                callable $read,
                callable $readLine,
            ): Maybe {
                return ($this->self)($read, $readLine);
            }

            /**
             * @psalm-mutation-free
             */
            public function filter(callable $predicate): Old
            {
                return $this->self->filter($predicate)->toOld();
            }

            /**
             * @psalm-mutation-free
             */
            public function map(callable $map): Old
            {
                return $this->self->map($map)->toOld();
            }

            /**
             * @psalm-mutation-free
             */
            public function flatMap(callable $map): Old
            {
                return $this->self->flatMap($map)->toOld();
            }
        };
    }
}
