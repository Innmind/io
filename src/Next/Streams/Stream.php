<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Streams;

use Innmind\IO\{
    Next\Streams\Stream\Read,
    Next\Streams\Stream\Write,
    Internal,
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
        private Internal\Stream\Readable $readable,
        private Internal\Stream\Writable $writable,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Previous $io,
        Internal\Stream\Readable $readable,
        Internal\Stream\Writable $writable,
    ): self {
        return new self($io, $readable, $writable);
    }

    public function read(): Read
    {
        return Read::of($this->io->readable()->wrap($this->readable));
    }

    public function write(): Write
    {
        return Write::of($this->writable);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function close(): Maybe
    {
        return $this->readable->close()->maybe();
    }
}
