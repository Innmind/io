<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Streams;

use Innmind\IO\Next\Streams\Stream\{
    Read,
    Write,
};
use Innmind\Immutable\{
    Maybe,
    SideEffect,
};

final class Stream
{
    /**
     * @param resource $resource
     */
    private function __construct(
        private $resource,
    ) {
    }

    /**
     * @internal
     *
     * @param resource $resource
     */
    public static function of($resource): self
    {
        return new self($resource);
    }

    public function read(): Read
    {
        return Read::of($this->resource);
    }

    public function write(): Write
    {
        return Write::of($this->resource);
    }

    /**
     * @return Maybe<SideEffect>
     */
    public function close(): Maybe
    {
        return Maybe::just(new SideEffect);
    }
}
