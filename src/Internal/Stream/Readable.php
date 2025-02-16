<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

use Innmind\Immutable\{
    Str,
    Maybe,
};

interface Readable extends Stream
{
    /**
     * @param positive-int|null $length When omitted will read the remaining of the stream
     *
     * @return Maybe<Str>
     */
    public function read(?int $length = null): Maybe;

    /**
     * @return Maybe<Str>
     */
    public function readLine(): Maybe;

    /**
     * @return Maybe<string>
     */
    public function toString(): Maybe;
}
