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
final class Line implements Implementation
{
    /**
     * @psalm-mutation-free
     */
    private function __construct()
    {
    }

    #[\Override]
    public function __invoke(Reader|Reader\Buffer $reader): Maybe
    {
        return $reader->readLine();
    }

    /**
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self;
    }
}
