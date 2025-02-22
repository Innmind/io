<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Writable;

use Innmind\IO\Internal\Stream\{
    Stream as StreamInterface,
    Stream\Stream as Base,
    Writable,
    Stream\Position,
    DataPartiallyWritten,
    FailedToWriteToStream,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
    SideEffect,
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

    #[\Override]
    public function resource()
    {
        return $this->resource;
    }

    #[\Override]
    public function write(Str $data): Either
    {
        if ($this->closed()) {
            /** @var Either<FailedToWriteToStream|DataPartiallyWritten, SideEffect> */
            return Either::left(new FailedToWriteToStream);
        }

        $written = @\fwrite($this->resource, $data->toString());

        if ($written === false) {
            /** @var Either<FailedToWriteToStream|DataPartiallyWritten, SideEffect> */
            return Either::left(new FailedToWriteToStream);
        }

        if ($written !== $data->length()) {
            /** @var Either<FailedToWriteToStream|DataPartiallyWritten, SideEffect> */
            return Either::left(new DataPartiallyWritten($data, $written));
        }

        /** @var Either<FailedToWriteToStream|DataPartiallyWritten, SideEffect> */
        return Either::right(new SideEffect);
    }

    #[\Override]
    public function position(): Position
    {
        return $this->stream->position();
    }

    #[\Override]
    public function rewind(): Either
    {
        return $this->stream->rewind();
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
}
