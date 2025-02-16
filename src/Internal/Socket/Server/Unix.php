<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Server;

use Innmind\IO\Internal\Socket\{
    Server,
    Address\Unix as Address,
};
use Innmind\IO\Internal\Stream\{
    Stream\Stream,
    Stream\Position,
    Stream\Size,
    Stream\Position\Mode,
    PositionNotSeekable,
};
use Innmind\Immutable\{
    Maybe,
    Either,
    SideEffect,
    Str,
};

final class Unix implements Server
{
    private string $path;
    /** @var resource */
    private $resource;
    private Stream $stream;

    /**
     * @param resource $socket
     */
    private function __construct(Address $path, $socket)
    {
        $this->path = $path->toString();
        $this->resource = $socket;
        $this->stream = Stream::of($socket);
    }

    /**
     * @return Maybe<self>
     */
    public static function of(Address $path): Maybe
    {
        $socket = @\stream_socket_server('unix://'.$path->toString());

        if ($socket === false) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        return Maybe::just(new self($path, $socket));
    }

    /**
     * On open failure it will try to delete existing socket file the ntry to
     * reopen the socket connection
     *
     * @return Maybe<self>
     */
    public static function recoverable(Address $path): Maybe
    {
        return self::of($path)->otherwise(static function() use ($path) {
            @\unlink($path->toString());

            return self::of($path);
        });
    }

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
    public function resource()
    {
        return $this->resource;
    }

    public function close(): Either
    {
        if (!$this->closed()) {
            return $this
                ->stream
                ->close()
                ->map(function($sideEffect) {
                    @\unlink($this->path);

                    return $sideEffect;
                });
        }

        return Either::right(new SideEffect);
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
        /** @var Maybe<Str> */
        return Maybe::nothing();
    }

    public function readLine(): Maybe
    {
        /** @var Maybe<Str> */
        return Maybe::nothing();
    }

    public function toString(): Maybe
    {
        /** @var Maybe<string> */
        return Maybe::nothing();
    }
}
