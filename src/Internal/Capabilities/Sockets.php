<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\Internal\Stream;
use Innmind\Immutable\Maybe;

final class Sockets
{
    private function __construct()
    {
    }

    /**
     * @internal
     */
    public static function of(): self
    {
        return new self;
    }

    public function clients(): Sockets\Clients
    {
        return Sockets\Clients::of();
    }

    public function servers(): Sockets\Servers
    {
        return Sockets\Servers::of();
    }

    /**
     * @return Maybe<array{Stream, Stream}>
     */
    public function pair(): Maybe
    {
        $pairs = @\stream_socket_pair(
            \STREAM_PF_UNIX,
            \STREAM_SOCK_STREAM,
            \STREAM_IPPROTO_IP,
        );

        if ($pairs === false) {
            /** @var Maybe<array{Stream, Stream}> */
            return Maybe::nothing();
        }

        return Maybe::just([
            Stream::of($pairs[0]),
            Stream::of($pairs[1]),
        ]);
    }
}
