<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Server;

use Innmind\IO\{
    Internal\Socket\Server,
    Internal\Stream,
    Exception\RuntimeException,
};
use Innmind\Immutable\Attempt;

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
    public function accept(): Attempt
    {
        $socket = @\stream_socket_accept($this->resource());

        if ($socket === false) {
            /** @var Attempt<Stream> */
            return Attempt::error(new RuntimeException('Failed to accept new connection'));
        }

        /** @var Attempt<Stream> */
        return Attempt::result(Stream::of($socket));
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
    public function close(): Attempt
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
