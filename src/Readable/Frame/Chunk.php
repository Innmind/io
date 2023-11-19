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
        return $read($this->size);
    }

    /**
     * @param positive-int $size
     */
    public static function of(int $size): self
    {
        return new self($size);
    }

    public function map(callable $map): Frame
    {
        return Map::of($this, $map);
    }

    public function flatMap(callable $map): Frame
    {
        return FlatMap::of($this, $map);
    }
}
