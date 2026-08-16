<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities\Sockets;

use Innmind\IO\{
    Sockets\Internet\Transport,
    Sockets\Unix\Address,
    Internal\Stream,
    Exception\RuntimeException,
};
use Innmind\Url\Authority;
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
final class Clients
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

    /**
     * @return Attempt<Stream>
     */
    public function internet(Transport $transport, Authority $authority): Attempt
    {
        $address = \sprintf(
            '%s://%s',
            $transport->toString(),
            $authority->toString(),
        );
        $socket = @\stream_socket_client(
            $address,
            context: $transport->asContext(),
        );

        if ($socket === false) {
            /** @var Attempt<Stream> */
            return Attempt::error(new RuntimeException("Failed to open socket at '$address'"));
        }

        return Attempt::result(Stream::of($socket));
    }

    /**
     * @return Attempt<Stream>
     */
    public function unix(Address $path): Attempt
    {
        $socket = @\stream_socket_client('unix://'.$path->toString());

        if ($socket === false) {
            /** @var Attempt<Stream> */
            return Attempt::error(new RuntimeException("Failed to open socket at '{$path->toString()}'"));
        }

        return Attempt::result(Stream::of($socket));
    }
}
