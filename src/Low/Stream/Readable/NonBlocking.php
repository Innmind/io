<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Stream\Readable;

use Innmind\IO\Low\Stream\{
    Readable,
    Stream\Position,
    Stream\Position\Mode,
    Exception\NonBlockingModeNotSupported,
};
use Innmind\Immutable\{
    Maybe,
    Either,
};

final class NonBlocking implements Readable
{
    private Readable $stream;

    private function __construct(Readable $stream)
    {
        $resource = $stream->resource();
        $return = \stream_set_blocking($resource, false);

        if ($return === false) {
            throw new NonBlockingModeNotSupported;
        }

        $_ = \stream_set_write_buffer($resource, 0);
        $_ = \stream_set_read_buffer($resource, 0);

        $this->stream = $stream;
    }

    public static function of(Readable $stream): self
    {
        return new self($stream);
    }

    /**
     * @psalm-mutation-free
     */
    public function resource()
    {
        return $this->stream->resource();
    }

    public function read(?int $length = null): Maybe
    {
        return $this->stream->read($length);
    }

    public function readLine(): Maybe
    {
        return $this->stream->readLine();
    }

    public function position(): Position
    {
        return $this->stream->position();
    }

    /** @psalm-suppress InvalidReturnType */
    public function seek(Position $position, ?Mode $mode = null): Either
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->stream->seek($position, $mode)->map(fn() => $this);
    }

    /** @psalm-suppress InvalidReturnType */
    public function rewind(): Either
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->stream->rewind()->map(fn() => $this);
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
        return $this->stream->size();
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

    public function toString(): Maybe
    {
        return $this->stream->toString();
    }
}
