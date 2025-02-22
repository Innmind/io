<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Streams;

use Innmind\IO\{
    Next\Streams\Stream\Read,
    Next\Streams\Stream\Write,
    Internal,
    Internal\Stream\Streams as Capabilities,
    IO as Previous,
};
use Innmind\Immutable\{
    Maybe,
    SideEffect,
};

final class Stream
{
    private function __construct(
        private Previous $io,
        private Capabilities $capabilities,
        private Internal\Stream\Implementation $stream,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Previous $io,
        Capabilities $capabilities,
        Internal\Stream\Implementation $stream,
    ): self {
        return new self($io, $capabilities, $stream);
    }

    public function read(): Read
    {
        return Read::of(
            $this->capabilities,
            $this->io->readable()->wrap($this->stream),
        );
    }

    public function write(): Write
    {
        return Write::of($this->stream);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function close(): Maybe
    {
        return $this->stream->close()->maybe();
    }
}
