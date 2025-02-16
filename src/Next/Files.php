<?php
declare(strict_types = 1);

namespace Innmind\IO\Next;

use Innmind\IO\{
    Next\Files\Read,
    Next\Files\Write,
    IO as Previous,
    Internal\Stream\Streams,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
};

final class Files
{
    private function __construct(
        private Previous $io,
        private Streams $capabilities,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Previous $io, Streams $capabilities): self
    {
        return new self($io, $capabilities);
    }

    public function read(Path $path): Read
    {
        return Read::of($this->io, $this->capabilities, $path);
    }

    public function write(Path $path): Write
    {
        return Write::of($this->capabilities, $path);
    }

    /**
     * @param Sequence<Str> $chunks
     *
     * @return Maybe<Read>
     */
    public function temporary(Sequence $chunks): Maybe
    {
        $tmp = $this->capabilities->temporary()->new();
        $io = $this->io->readable();

        return Write::temporary($tmp)
            ->sink($chunks)
            ->map(static fn() => Read::temporary($io, $tmp));
    }
}
