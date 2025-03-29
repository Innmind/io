<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities\Sockets;

use Innmind\IO\{
    Sockets\Internet\Transport,
    Sockets\Unix\Address,
    Internal\Stream,
    Internal\Socket\Server,
    Internal\Socket\Server\Internet,
    Internal\Socket\Server\Unix,
    Exception\RuntimeException,
};
use Innmind\IP\IP;
use Innmind\Url\Authority\Port;
use Innmind\Immutable\Attempt;

final class Servers
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
     * @return Attempt<Server>
     */
    public function internet(
        Transport $transport,
        IP $ip,
        Port $port,
    ): Attempt {
        $address = \sprintf(
            '%s://%s:%s',
            $transport->toString(),
            $ip->toString(),
            $port->toString(),
        );
        $socket = @\stream_socket_server($address);

        if ($socket === false) {
            /** @var Attempt<Server> */
            return Attempt::error(new RuntimeException("Failed to open server '$address'"));
        }

        return Attempt::result(
            Internet::of(
                Stream::of(
                    $socket,
                ),
            ),
        );
    }

    /**
     * @return Attempt<Server>
     */
    public function unix(Address $path): Attempt
    {
        $socket = @\stream_socket_server('unix://'.$path->toString());

        if ($socket === false) {
            /** @var Attempt<Server> */
            return Attempt::error(new RuntimeException("Failed to open server '{$path->toString()}'"));
        }

        return Attempt::result(
            Unix::of(
                $path,
                Stream::of($socket),
            ),
        );
    }

    /**
     * On open failure it will try to delete existing socket file the ntry to
     * reopen the socket connection
     *
     * @return Attempt<Server>
     */
    public function takeOver(Address $path): Attempt
    {
        return $this
            ->unix($path)
            ->recover(function() use ($path) {
                @\unlink($path->toString());

                return $this->unix($path);
            });
    }
}
