<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable\Frame;

use Innmind\IO\Readable\Frame;
use Innmind\Immutable\{
    Maybe,
    Str,
};

/**
 * @implements Frame<Str>
 */
final class Chunk implements Frame
{
    /** @var positive-int */
    private int $size;

    /**
     * @psalm-mutation-free
     *
     * @param positive-int $size
     */
    private function __construct(int $size)
    {
        $this->size = $size;
    }

    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe {
        return $read($this->size)->filter(
            fn($chunk) => $chunk->length() === $this->size,
        );
    }

    /**
     * @psalm-pure
     *
     * @param positive-int $size
     */
    public static function of(int $size): self
    {
        return new self($size);
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(Str): bool $predicate
     *
     * @return Frame<Str>
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
     * @param callable(Str): U $map
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
     * @param callable(Str): Frame<U> $map
     *
     * @return Frame<U>
     */
    public function flatMap(callable $map): Frame
    {
        return FlatMap::of($this, $map);
    }
}
