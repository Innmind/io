<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Socket\Server\Connection;

use Innmind\IO\Low\Socket\{
    Server\Connection,
};
use Innmind\IO\Low\Stream\{
    Writable,
    Stream\Bidirectional,
    Stream\Position,
    Stream\Size,
    Stream\Position\Mode,
    PositionNotSeekable,
    DataPartiallyWritten,
    FailedToWriteToStream,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
};

final class Stream implements Connection
{
    private Bidirectional $stream;

    /**
     * @param resource $resource
     */
    private function __construct($resource)
    {
        $this->stream = Bidirectional::of($resource);
    }

    /**
     * @param resource $resource
     */
    public static function of($resource): self
    {
        return new self($resource);
    }

    /**
     * @psalm-mutation-free
     */
    public function resource()
    {
        return $this->stream->resource();
    }

    public function close(): Either
    {
        return $this->stream->close();
    }

    /**
     * @psalm-mutation-free
     */
    public function closed(): bool
    {
        return $this->stream->closed();
    }

    public function position(): Position
    {
        return $this->stream->position();
    }

    public function seek(Position $position, ?Mode $mode = null): Either
    {
        return Either::left(new PositionNotSeekable);
    }

    public function rewind(): Either
    {
        return Either::left(new PositionNotSeekable);
    }

    /**
     * @psalm-mutation-free
     */
    public function end(): bool
    {
        return $this->stream->end();
    }

    /**
     * @psalm-mutation-free
     */
    public function size(): Maybe
    {
        /** @var Maybe<Size> */
        return Maybe::nothing();
    }

    public function read(?int $length = null): Maybe
    {
        return $this->stream->read($length);
    }

    public function readLine(): Maybe
    {
        return $this->stream->readLine();
    }

    public function write(Str $data): Either
    {
        /** @var Either<DataPartiallyWritten|FailedToWriteToStream, Writable> */
        return $this
            ->stream
            ->write($data)
            ->map(fn() => $this);
    }

    public function toString(): Maybe
    {
        /** @var Maybe<string> */
        return Maybe::nothing();
    }
}
