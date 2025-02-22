<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Server;

use Innmind\IO\Internal\Socket\Server;
use Innmind\IO\Internal\Stream\Stream;
use Innmind\Immutable\{
    Maybe,
    Either,
};

final class Internet implements Server
{
    private function __construct(
        private Stream $stream,
    ) {
    }

    public static function of(Stream $stream): self
    {
        return new self($stream);
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
