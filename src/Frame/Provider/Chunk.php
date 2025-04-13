<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame\Provider;

use Innmind\IO\{
    Frame,
    Frame\Implementation,
};
use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
final class Chunk
{
    /**
     * @param pure-Closure(Implementation): Frame $build
     * @param int<1, max> $size
     */
    private function __construct(
        private \Closure $build,
        private int $size,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param pure-Closure(Implementation): Frame $build
     * @param int<1, max> $size
     */
    public static function of(
        \Closure $build,
        int $size,
    ): self {
        return new self($build, $size);
    }

    /**
     * This will make sure the read chunk is of the specified size
     *
     * @return Frame<Str>
     */
    public function strict(): Frame
    {
        $size = $this->size;

        return $this
            ->loose()
            ->filter(static fn($chunk) => $chunk->length() === $size);
    }

    /**
     * The read chunk may be shorter than the specified size.
     *
     * @return Frame<Str>
     */
    public function loose(): Frame
    {
        /** @var Frame<Str> */
        return ($this->build)(Frame\Chunk::of($this->size));
    }
}
