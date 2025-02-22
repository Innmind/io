<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Server;

use Innmind\IO\{
    Internal\Socket\Server,
    Next\Sockets\Internet\Transport,
};
use Innmind\IO\Internal\Stream\Stream;
use Innmind\IP\IP;
use Innmind\Url\Authority\Port;
use Innmind\Immutable\{
    Maybe,
    Either,
};

final class Internet implements Server
{
    private Stream $stream;

    /**
     * @param resource $socket
     */
    private function __construct($socket)
    {
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
            /** @var Maybe<Stream> */
            return Maybe::nothing();
        }

        /** @var Maybe<Stream> */
        return Maybe::just(Stream::of($socket));
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
}
