<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket;

use Innmind\IO\Internal\Stream;
use Innmind\Immutable\{
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
     * @return Attempt<Stream>
     */
    public function accept(): Attempt;

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
