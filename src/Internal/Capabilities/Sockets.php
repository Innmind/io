<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\{
    Internal\Stream,
    Exception\RuntimeException,
};
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
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
     * @return Attempt<array{Stream, Stream}>
     */
    public function pair(): Attempt
    {
        $pairs = @\stream_socket_pair(
            \STREAM_PF_UNIX,
            \STREAM_SOCK_STREAM,
            \STREAM_IPPROTO_IP,
        );

        if ($pairs === false) {
            /** @var Attempt<array{Stream, Stream}> */
            return Attempt::error(new RuntimeException('Failed to create a socket pair'));
        }

        return Attempt::result([
            Stream::of($pairs[0]),
            Stream::of($pairs[1]),
        ]);
    }
}
