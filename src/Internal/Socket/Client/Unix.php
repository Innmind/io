<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Client;

use Innmind\IO\Next\Sockets\Unix\Address;
use Innmind\IO\Internal\Stream\Stream;
use Innmind\Immutable\Maybe;

final class Unix
{
    private function __construct()
    {
    }

    /**
     * @return Maybe<Stream>
     */
    public static function of(Address $path): Maybe
    {
        $socket = @\stream_socket_client('unix://'.$path->toString());

        if ($socket === false) {
            /** @var Maybe<Stream> */
            return Maybe::nothing();
        }

        return Maybe::just(Stream::of($socket));
    }
}
