<?php
declare(strict_types = 1);

namespace Innmind\IO\Next;

use Innmind\IO\Next\Files\{
    Read,
    Write,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
};

final class Files
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

    public function read(Path $path): Read
    {
        return Read::of($path);
    }

    public function write(Path $path): Write
    {
        return Write::of($path);
    }

    /**
     * @param Sequence<Str> $chunks
     *
     * @return Maybe<Read>
     */
    public function temporary(Sequence $chunks): Maybe
    {
        /** @var Maybe<Read> */
        return Maybe::nothing();
    }
}
