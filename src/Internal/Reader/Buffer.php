<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Reader;

use Innmind\Immutable\{
    Str,
    Maybe,
};

/**
 * @internal
 */
final class Buffer
{
    private function __construct(
        private Str $buffer,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Str $buffer): self
    {
        return new self($buffer);
    }

    /**
     * @param ?int<1, max> $size
     *
     * @return Maybe<Str>
     */
    public function read(?int $size = null): Maybe
    {
        if (\is_null($size)) {
            throw new \LogicException('Only fixed size frames are bufferable');
        }

        $chunk = $this->buffer->take($size);
        $this->buffer = $this->buffer->drop($size);

        return Maybe::just($chunk);
    }

    /**
     * @return Maybe<Str>
     */
    public function readLine(): Maybe
    {
        throw new \LogicException('Only fixed size frames are bufferable');
    }
}
