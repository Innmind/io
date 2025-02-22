<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Client;

use Innmind\IO\{
    Internal\Socket\Client,
    Next\Sockets\Internet\Transport
};
use Innmind\IO\Internal\Stream\{
    Size,
    PositionNotSeekable,
    Implementation,
};
use Innmind\Url\Authority;
use Innmind\Immutable\{
    Str,
    Maybe,
    Either,
};

final class Internet implements Client
{
    private Implementation $stream;

    /**
     * @param resource $socket
     */
    private function __construct($socket)
    {
        $this->stream = Implementation::of($socket);
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
