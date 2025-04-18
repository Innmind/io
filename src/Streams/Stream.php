<?php
declare(strict_types = 1);

namespace Innmind\IO\Streams;

use Innmind\IO\{
    Streams\Stream\Read,
    Streams\Stream\Write,
    Internal,
    Internal\Capabilities,
};
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

final class Stream
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
        return Read::of(
            $this->write(),
            $this->capabilities,
            $this->stream,
        );
    }

    public function write(): Write
    {
        return Write::of($this->capabilities->watch(), $this->stream);
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function close(): Attempt
    {
        return $this->stream->close();
    }
}
