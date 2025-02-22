<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Capabilities\Sockets;

use Innmind\IO\{
    Next\Sockets\Internet\Transport,
    Next\Sockets\Unix\Address,
    Internal\Stream\Stream,
    Internal\Socket\Server,
    Internal\Socket\Server\Internet,
    Internal\Socket\Server\Unix,
};
use Innmind\IP\IP;
use Innmind\Url\Authority\Port;
use Innmind\Immutable\Maybe;

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
     * @return Maybe<Server>
     */
    public function internet(
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
            /** @var Maybe<Server> */
            return Maybe::nothing();
        }

        return Maybe::just(
            Internet::of(
                Stream::of(
                    $socket,
                ),
            ),
        );
    }

    /**
     * @return Maybe<Server>
     */
    public function unix(Address $path): Maybe
    {
        $socket = @\stream_socket_server('unix://'.$path->toString());

        if ($socket === false) {
            /** @var Maybe<Server> */
            return Maybe::nothing();
        }

        return Maybe::just(
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
     * @return Maybe<Server>
     */
    public function takeOver(Address $path): Maybe
    {
        return $this
            ->unix($path)
            ->otherwise(function() use ($path) {
                @\unlink($path->toString());

                return $this->unix($path);
            });
    }
}
