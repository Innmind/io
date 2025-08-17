<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Async;

use Innmind\IO\Internal\Watch\Ready;
use Innmind\Immutable\Attempt;

/**
 * @internal
 * @psalm-immutable
 */
final class Resumable
{
    /**
     * @param Attempt<Ready> $ready
     */
    private function __construct(
        private Attempt $ready,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param Attempt<Ready> $ready
     */
    public static function of(Attempt $ready): self
    {
        return new self($ready);
    }

    /**
     * @return Attempt<Ready>
     */
    public function unwrap(): Attempt
    {
        return $this->ready;
    }
}
