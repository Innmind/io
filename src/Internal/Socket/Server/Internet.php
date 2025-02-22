<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Server;

use Innmind\IO\Internal\Socket\{
    Server,
    Internet\Transport,
};
use Innmind\IO\Internal\Stream\{
    Stream\Stream,
    Stream\Position,
    Stream\Size,
    Stream\Position\Mode,
    PositionNotSeekable,
};
use Innmind\IP\IP;
use Innmind\Url\Authority\Port;
use Innmind\Immutable\{
    Maybe,
    Either,
    Str,
};

final class Internet implements Server
{
    /** @var resource */
    private $resource;
    private Stream $stream;

    /**
     * @param resource $socket
     */
    private function __construct($socket)
    {
        $this->resource = $socket;
        $this->stream = Stream::of($socket);
    }

    /**
     * @return Maybe<self>
     */
    public static function of(
        Transport $transport,
        IP $ip,
        Port $port,
    ): Maybe {
        $socket = @\stream_socket_server(\sprintf(
            '%s://%s:%s',
            $transport->toString(),
            $ip->toString(),
            $port->toString(),
        ));

        if ($socket === false) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        return Maybe::just(new self($socket));
    }

    #[\Override]
    public function accept(): Maybe
    {
        $socket = @\stream_socket_accept($this->resource());

        if ($socket === false) {
            /** @var Maybe<Connection> */
            return Maybe::nothing();
        }

        /** @var Maybe<Connection> */
        return Maybe::just(Connection\Stream::of($socket));
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function resource()
    {
        return $this->resource;
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
        /** @var Maybe<Str> */
        return Maybe::nothing();
    }

    #[\Override]
    public function readLine(): Maybe
    {
        /** @var Maybe<Str> */
        return Maybe::nothing();
    }

    #[\Override]
    public function toString(): Maybe
    {
        /** @var Maybe<string> */
        return Maybe::nothing();
    }
}
