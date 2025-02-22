<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Server;

use Innmind\IO\{
    Internal\Socket\Server,
    Next\Sockets\Unix\Address,
};
use Innmind\IO\Internal\Stream;
use Innmind\Immutable\{
    Maybe,
    Either,
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
