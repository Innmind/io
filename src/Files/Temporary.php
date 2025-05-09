<?php
declare(strict_types = 1);

namespace Innmind\IO\Files;

use Innmind\IO\{
    Files\Temporary\Pull,
    Internal,
    Internal\Capabilities,
};
use Innmind\Immutable\Attempt;

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

    /**
     * This method is required for innmind/http-transport as the Curl
     * implementation requires to expose the raw resource.
     *
     * @internal
     */
    public function internal(): Internal\Stream
    {
        return $this->stream;
    }

    public function read(): Read
    {
        return Read::temporary($this->capabilities, $this->stream);
    }

    /**
     * @return Attempt<Pull>
     */
    public function pull(): Attempt
    {
        return $this->stream->rewind()->map(
            fn() => Pull::of($this->capabilities, $this->stream),
        );
    }
}
