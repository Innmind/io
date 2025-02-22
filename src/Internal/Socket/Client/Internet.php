<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Client;

use Innmind\IO\Next\Sockets\Internet\Transport;
use Innmind\IO\Internal\Stream\Stream;
use Innmind\Url\Authority;
use Innmind\Immutable\Maybe;

final class Internet
{
    private function __construct()
    {
    }

    /**
     * @return Maybe<Stream>
     */
    public static function of(Transport $transport, Authority $authority): Maybe
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
}
