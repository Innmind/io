<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\IO\Internal\Reader;
use Innmind\Immutable\{
    Maybe,
    Str,
};

/**
 * @internal
 * @implements Implementation<Str>
 */
final class Chunk implements Implementation
{
    /**
     * @psalm-mutation-free
     *
     * @param int<1, max> $size
     */
    private function __construct(
        private int $size,
    ) {
    }

    #[\Override]
    public function __invoke(Reader|Reader\Buffer $reader): Maybe
    {
        return $reader->read($this->size)->maybe();
    }

    /**
     * @psalm-pure
     *
     * @param int<1, max> $size
     */
    public static function of(int $size): self
    {
        return new self($size);
    }
}
