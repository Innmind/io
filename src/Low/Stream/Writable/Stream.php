<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Stream\Writable;

use Innmind\IO\Low\Stream\{
    Stream as StreamInterface,
    Stream\Stream as Base,
    Writable,
    Stream\Position,
    Stream\Position\Mode,
    DataPartiallyWritten,
    FailedToWriteToStream,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
};

final class Stream implements Writable
{
    /** @var resource */
    private $resource;
    private StreamInterface $stream;
    private bool $closed = false;

    /**
     * @param resource $resource
     */
    private function __construct($resource)
    {
        $this->stream = Base::of($resource);
        $this->resource = $resource;
    }

    /**
     * @param resource $resource
     */
    public static function of($resource): self
    {
        return new self($resource);
    }

    public function resource()
    {
        return $this->resource;
    }

    public function write(Str $data): Either
    {
        if ($this->closed()) {
            /** @var Either<FailedToWriteToStream|DataPartiallyWritten, Writable> */
            return Either::left(new FailedToWriteToStream);
        }

        $written = @\fwrite($this->resource, $data->toString());

        if ($written === false) {
            /** @var Either<FailedToWriteToStream|DataPartiallyWritten, Writable> */
            return Either::left(new FailedToWriteToStream);
        }

        if ($written !== $data->length()) {
            /** @var Either<FailedToWriteToStream|DataPartiallyWritten, Writable> */
            return Either::left(new DataPartiallyWritten($data, $written));
        }

        /** @var Either<FailedToWriteToStream|DataPartiallyWritten, Writable> */
        return Either::right($this);
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
}
