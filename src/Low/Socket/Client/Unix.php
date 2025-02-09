<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Socket\Client;

use Innmind\IO\Low\Socket\{
    Client,
    Address\Unix as Address,
};
use Innmind\IO\Low\Stream\{
    Stream,
    Writable,
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

final class Unix implements Client
{
    private Stream\Bidirectional $stream;

    /**
     * @param resource $socket
     */
    private function __construct($socket)
    {
        $this->stream = Stream\Bidirectional::of($socket);
    }

    /**
     * @return Maybe<self>
     */
    public static function of(Address $path): Maybe
    {
        $socket = @\stream_socket_client('unix://'.$path->toString());

        if ($socket === false) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        return Maybe::just(new self($socket));
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
