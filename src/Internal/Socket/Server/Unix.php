<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Server;

use Innmind\IO\{
    Internal\Socket\Server,
    Next\Sockets\Unix\Address,
};
use Innmind\IO\Internal\Stream\{
    Implementation,
};
use Innmind\Immutable\{
    Maybe,
    Either,
    SideEffect,
};

final class Unix implements Server
{
    private string $path;
    private Implementation $stream;

    /**
     * @param resource $socket
     */
    private function __construct(Address $path, $socket)
    {
        $this->path = $path->toString();
        $this->stream = Implementation::of($socket);
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

    #[\Override]
    public function accept(): Maybe
    {
        $socket = @\stream_socket_accept($this->resource());

        if ($socket === false) {
            /** @var Maybe<Implementation> */
            return Maybe::nothing();
        }

        /** @var Maybe<Implementation> */
        return Maybe::just(Implementation::of($socket));
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
    #[\Override]
    public function closed(): bool
    {
        return $this->stream->closed();
    }
}
