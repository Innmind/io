<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Async;

use Innmind\IO\Internal\Watch\Ready;

/**
 * @internal
 */
final class Resumable
{
    private function __construct(
        private Ready $ready,
    ) {
    }

    public static function of(Ready $ready): self
    {
        return new self($ready);
    }

    public function ready(): Ready
    {
        return $this->ready;
    }
}
