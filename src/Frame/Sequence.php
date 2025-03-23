<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\IO\{
    Frame,
    Internal\Reader,
};
use Innmind\Immutable\{
    Sequence as Seq,
    Attempt,
};

/**
 * @template T
 * @implements Implementation<Seq<Attempt<T>>>
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
    public function __invoke(Reader|Reader\Buffer $reader): Attempt
    {
        $frame = $this->frame;
        $frames = Seq::lazy(static function() use ($reader, $frame) {
            while (true) {
                yield $frame($reader);
            }
        });

        return Attempt::result($frames);
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
