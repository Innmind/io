<?php
declare(strict_types = 1);

namespace Innmind\IO\Readable\Frame;

use Innmind\IO\{
    Readable\Frame,
    Exception\FailedToLoadStream,
};
use Innmind\Immutable\{
    Sequence as Seq,
    Maybe,
    Str,
};

/**
 * @implements Frame<Seq<Str>>
 */
final class Chunks implements Frame
{
    /** @var positive-int */
    private int $size;
    /** @var ?positive-int */
    private ?int $aggregate;
    /** @var callable(Str): bool */
    private $until;

    /**
     * @psalm-mutation-free
     *
     * @param positive-int $size
     * @param ?positive-int $aggregate
     * @param callable(Str): bool $until
     */
    private function __construct(int $size, ?int $aggregate, callable $until)
    {
        $this->size = $size;
        $this->aggregate = $aggregate;
        $this->until = $until;
    }

    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe {
        $chunks = Seq::lazy(function() use ($read) {
            while (true) {
                yield $read($this->size)->match(
                    static fn($chunk) => $chunk,
                    static fn() => throw new FailedToLoadStream,
                );
            }
        });

        $aggregate = $this->aggregate;

        if (\is_int($aggregate)) {
            // We chunk before calling the aggregate to avoid loading 2 chunks
            // in memory in case the expected aggregate is smaller than the
            // chunk size.
            $chunks = $chunks
                ->flatMap(static fn($chunk) => $chunk->chunk($aggregate))
                ->aggregate(
                    static fn(Str $a, $b) => $a
                        ->append($b)
                        ->chunk($aggregate),
                );
        }

        $chunks = $chunks->takeWhile(fn($chunk) => !($this->until)($chunk));

        return Maybe::just($chunks);
    }

    /**
     * Beware, this produces a lazy Sequence so when you compose many of them
     * the order of operations may not be the one you expect.
     *
     * It it fails to read a chunk it will throw an error.
     *
     * @psalm-pure
     *
     * @param positive-int $size
     */
    public static function of(int $size): self
    {
        return new self($size, null, static fn() => false);
    }

    /**
     * @psalm-mutation-free
     *
     * @param positive-int $size The expected size of the string passed to the predicate
     * @param callable(Str): bool $predicate
     */
    public function until(int $size, callable $predicate): self
    {
        return new self($this->size, $size, $predicate);
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(Seq<Str>): bool $predicate
     *
     * @return Frame<Seq<Str>>
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
     * @param callable(Seq<Str>): U $map
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
     * @param callable(Seq<Str>): Frame<U> $map
     *
     * @return Frame<U>
     */
    public function flatMap(callable $map): Frame
    {
        return FlatMap::of($this, $map);
    }
}
