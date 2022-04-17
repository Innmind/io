<?php
declare(strict_types = 1);

namespace Innmind\IO\Stream;

use Innmind\Immutable\{
    Maybe,
    Str,
};

/**
 * @psalm-immutable
 */
interface Writable
{
    /**
     * @return Maybe<self> Returns nothing when the stream can no longer be written
     */
    public function write(Str $data): Maybe;
}
