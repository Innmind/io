<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

use Innmind\IO\Internal\Stream\Watch\Ready;
use Innmind\Immutable\Maybe;

interface Watch
{
    /**
     * @return Maybe<Ready> Returns nothing when it fails to lookup the streams
     */
    public function __invoke(): Maybe;

    /**
     * @psalm-mutation-free
     */
    public function forRead(Readable $read, Readable ...$reads): self;

    /**
     * @psalm-mutation-free
     */
    public function forWrite(Writable $write, Writable ...$writes): self;

    /**
     * @psalm-mutation-free
     */
    public function unwatch(Stream $stream): self;
}
