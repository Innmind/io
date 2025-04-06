<?php
declare(strict_types = 1);

namespace Innmind\IO\Files;

use Innmind\IO\{
    Internal,
    Internal\Capabilities,
};

final class Temporary
{
    private function __construct(
        private Capabilities $capabilities,
        private Internal\Stream $stream,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Capabilities $capabilities,
        Internal\Stream $stream,
    ): self {
        return new self($capabilities, $stream);
    }

    public function read(): Read
    {
        return Read::temporary($this->capabilities, $this->stream);
    }
}
