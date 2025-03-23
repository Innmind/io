<?php
declare(strict_types = 1);

namespace Innmind\IO\Frame;

use Innmind\Immutable\{
    Maybe,
    Str,
};

/**
 * @internal
 * @implements Implementation<Str>
 */
final class Line implements Implementation
{
    /**
     * @psalm-mutation-free
     */
    private function __construct()
    {
    }

    #[\Override]
    public function __invoke(
        callable $read,
        callable $readLine,
    ): Maybe {
        return $readLine();
    }

    /**
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self;
    }
}
