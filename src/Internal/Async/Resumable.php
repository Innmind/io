<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Async;

use Innmind\IO\Internal\Watch\Ready;
use Innmind\Immutable\Attempt;

/**
 * @internal
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
     * @param Attempt<Ready> $ready
     */
    public static function of(Attempt $ready): self
    {
        return new self($ready);
    }

    /**
     * @return Attempt<Ready>
     */
    public function ready(): Attempt
    {
        return $this->ready;
    }
}
