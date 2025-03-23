<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket;

use Innmind\IO\Internal\Stream;
use Innmind\Immutable\{
    Maybe,
    Attempt,
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
     * It returns a SideEffect instead of the stream on the result side because
     * you should no longer use the stream once it's closed.
     *
     * @return Attempt<SideEffect>
     */
    public function close(): Attempt;

    /**
     * @psalm-mutation-free
     */
    public function closed(): bool;
}
