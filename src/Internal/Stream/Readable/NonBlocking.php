<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Readable;

use Innmind\IO\Internal\Stream\{
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
    #[\Override]
    public function resource()
    {
        return $this->stream->resource();
    }

    #[\Override]
    public function read(?int $length = null): Maybe
    {
        return $this->stream->read($length);
    }

    #[\Override]
    public function readLine(): Maybe
    {
        return $this->stream->readLine();
    }

    #[\Override]
    public function position(): Position
    {
        return $this->stream->position();
    }

    /** @psalm-suppress InvalidReturnType */
    #[\Override]
    public function seek(Position $position, ?Mode $mode = null): Either
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->stream->seek($position, $mode)->map(fn() => $this);
    }

    /** @psalm-suppress InvalidReturnType */
    #[\Override]
    public function rewind(): Either
    {
        /** @psalm-suppress InvalidReturnStatement */
        return $this->stream->rewind()->map(fn() => $this);
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function end(): bool
    {
        return $this->stream->end();
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function size(): Maybe
    {
        return $this->stream->size();
    }

    #[\Override]
    public function close(): Either
    {
        return $this->stream->close();
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function closed(): bool
    {
        return $this->stream->closed();
    }

    #[\Override]
    public function toString(): Maybe
    {
        return $this->stream->toString();
    }
}
