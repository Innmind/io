<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities\Sockets;

use Innmind\IO\{
    Next\Sockets\Internet\Transport,
    Next\Sockets\Unix\Address,
    Internal\Stream,
};
use Innmind\Url\Authority;
use Innmind\Immutable\Maybe;

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
     * @return Maybe<Stream>
     */
    public function internet(Transport $transport, Authority $authority): Maybe
    {
        $socket = @\stream_socket_client(\sprintf(
            '%s://%s',
            $transport->toString(),
            $authority->toString(),
        ));

        if ($socket === false) {
            /** @var Maybe<Stream> */
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

        return Maybe::just(Stream::of($socket));
    }

    /**
     * @return Maybe<Stream>
     */
    public function unix(Address $path): Maybe
    {
        $socket = @\stream_socket_client('unix://'.$path->toString());

        if ($socket === false) {
            /** @var Maybe<Stream> */
            return Maybe::nothing();
        }

        return Maybe::just(Stream::of($socket));
    }
}
