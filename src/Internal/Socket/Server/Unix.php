<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Server;

use Innmind\IO\{
    Sockets\Unix\Address,
    Internal\Socket\Server,
    Internal\Stream,
    Exception\RuntimeException,
};
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

final class Unix implements Server
{
    private string $path;
    private Stream $stream;

    private function __construct(Address $path, Stream $stream)
    {
        $this->path = $path->toString();
        $this->stream = $stream;
    }

    public static function of(Address $path, Stream $stream): self
    {
        return new self($path, $stream);
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
        if (!$this->closed()) {
            return $this
                ->stream
                ->close()
                ->map(function($sideEffect) {
                    @\unlink($this->path);

                    return $sideEffect;
                });
        }

        return Attempt::result(new SideEffect);
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
