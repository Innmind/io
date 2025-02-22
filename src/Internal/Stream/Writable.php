<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

use Innmind\Immutable\{
    Str,
    Either,
    SideEffect,
};

interface Writable extends Stream
{
    /**
     * @return Either<FailedToWriteToStream|DataPartiallyWritten, SideEffect>
     */
    public function write(Str $data): Either;
}
