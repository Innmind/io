<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Server\Connection;

use Innmind\IO\Internal\Socket\{
    Server\Connection,
};
use Innmind\IO\Internal\Stream\{
    Stream\Bidirectional,
    Stream\Position,
    Stream\Size,
    PositionNotSeekable,
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
    #[\Override]
    public function resource()
    {
        return $this->stream->resource();
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
    public function position(): Position
    {
        return $this->stream->position();
    }

    #[\Override]
    public function rewind(): Either
    {
        return Either::left(new PositionNotSeekable);
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
        /** @var Maybe<Size> */
        return Maybe::nothing();
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
    public function write(Str $data): Either
    {
        return $this->stream->write($data);
    }
}
