<?php
declare(strict_types = 1);

namespace Innmind\IO\Simulation\Disk\File;

use Innmind\IO\Internal\Stream;

/**
 * @internal
 */
final class Content
{
    private function __construct(
        private Stream $stream,
    ) {
    }

    /**
     * @internal
     */
    public static function new(Stream $stream): self
    {
        return new self($stream);
    }

    public function stream(): Stream
    {
        return $this->stream;
    }

    public function read(): string
    {
        return $this
            ->stream
            ->rewind()
            ->flatMap(fn() => $this->stream->read())
            ->unwrap()
            ->toString();
    }
}
