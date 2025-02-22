<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Client;

use Innmind\IO\Internal\Socket\{
    Client,
    Internet\Transport,
};
use Innmind\IO\Internal\Stream\{
    Stream,
    Writable,
    Stream\Position,
    Stream\Size,
    Stream\Position\Mode,
    PositionNotSeekable,
    DataPartiallyWritten,
    FailedToWriteToStream,
};
use Innmind\Url\Authority;
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
};

final class Internet implements Client
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
    public static function of(Transport $transport, Authority $authority): Maybe
    {
        $socket = @\stream_socket_client(\sprintf(
            '%s://%s',
            $transport->toString(),
            $authority->toString(),
        ));

        if ($socket === false) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        /**
         * @psalm-suppress MissingClosureReturnType
         * @var resource
         */
        $socket = $transport
            ->options()
            ->reduce(
                $socket,
                static function($socket, string $key, int|bool|float|string|array $value) use ($transport) {
                    \stream_context_set_option($socket, $transport->toString(), $key, $value);

                    return $socket;
                },
            );

        return Maybe::just(new self($socket));
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
    public function seek(Position $position, ?Mode $mode = null): Either
    {
        return Either::left(new PositionNotSeekable);
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
        /** @var Either<DataPartiallyWritten|FailedToWriteToStream, Writable> */
        return $this
            ->stream
            ->write($data)
            ->map(fn() => $this);
    }

    #[\Override]
    public function toString(): Maybe
    {
        /** @var Maybe<string> */
        return Maybe::nothing();
    }
}
