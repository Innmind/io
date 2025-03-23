<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket;

use Innmind\IO\{
    Internal\Stream,
    Exception\FailedToCloseStream,
};
use Innmind\Immutable\{
    Maybe,
    Either,
    SideEffect,
};

interface Server
{
    /**
     * @psalm-mutation-free
     *
     * @return resource stream
     */
    public function resource();

    /**
     * @return Maybe<Stream>
     */
    public function accept(): Maybe;

    /**
     * It returns a SideEffect instead of the stream on the right hand size
     * because you should no longer use the stream once it's closed
     *
     * @return Either<FailedToCloseStream, SideEffect>
     */
    public function close(): Either;

    /**
     * @psalm-mutation-free
     */
    public function closed(): bool;
}
