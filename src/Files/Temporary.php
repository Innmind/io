<?php
declare(strict_types = 1);

namespace Innmind\IO\Files;

use Innmind\IO\{
    Files\Temporary\Pull,
    Files\Temporary\Push,
    Internal,
    Internal\Capabilities,
};
use Innmind\Immutable\{
    Attempt,
    SideEffect,
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
    #[\NoDiscard]
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
    #[\NoDiscard]
    public function internal(): Internal\Stream
    {
        return $this->stream;
    }

    #[\NoDiscard]
    public function read(): Read
    {
        return Read::temporary($this->capabilities, $this->stream);
    }

    /**
     * @return Attempt<Pull>
     */
    #[\NoDiscard]
    public function pull(): Attempt
    {
        return $this->stream->rewind()->map(
            fn() => Pull::of($this->capabilities, $this->stream),
        );
    }

    #[\NoDiscard]
    public function push(): Push
    {
        return Push::of($this->capabilities, $this->stream);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function close(): Attempt
    {
        return $this->stream->close();
    }
}
