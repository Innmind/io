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
}
