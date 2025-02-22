<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Streams;

use Innmind\IO\Internal\Stream\{
    Capabilities,
    Bidirectional,
    Implementation,
};

final class Temporary implements Capabilities\Temporary
{
    private function __construct()
    {
    }

    /**
     * @internal
     */
    public static function of(): self
    {
        return new self;
    }

    #[\Override]
    public function new(): Bidirectional
    {
        return Implementation::of(\fopen('php://temp', 'r+'));
    }
}
