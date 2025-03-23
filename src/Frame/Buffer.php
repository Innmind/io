<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\IO\{
    Frame,
    Internal\Reader,
};
use Innmind\Immutable\Maybe;

/**
 * Use this frame to hardcode a value inside a frame composition
 *
 * @internal
 * @template T
 * @implements Implementation<T>
 */
final class Buffer implements Implementation
{
    /**
     * @psalm-mutation-free
     *
     * @param int<1, max> $size
     * @param Implementation<T> $frame
     */
    private function __construct(
        private int $size,
        private Implementation $frame,
    ) {
    }

    #[\Override]
    public function __invoke(Reader|Reader\Buffer $reader): Maybe
    {
        $frame = $this->frame;

        return $reader
            ->read($this->size)
            ->maybe()
            ->flatMap(static fn($buffer) => $frame(Reader\Buffer::of($buffer)));
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param int<1, max> $size
     * @param Implementation<A> $frame
     *
     * @return self<A>
     */
    public static function of(int $size, Implementation $frame): self
    {
        return new self($size, $frame);
    }
}
