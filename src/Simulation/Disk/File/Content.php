<?php
declare(strict_types = 1);

namespace Innmind\IO\Simulation\Disk\File;

use Innmind\IO\Internal\Stream;
use Innmind\Immutable\Attempt;

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
    public static function of(Stream $stream): self
    {
        return new self($stream);
    }

    /**
     * @return Attempt<Stream>
     */
    public function stream(): Attempt
    {
        // By wrapping the stream each time it's accessed we allow to reset the
        // closed flag. This way it simulates a real file by "reopening it" each
        // time it's accessed.
        $stream = Stream::simulated($this->stream);

        return $stream
            ->rewind()
            ->map(static fn() => $stream);
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
