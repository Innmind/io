<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\IO\{
    Files\Read,
    Files\Write,
    Internal\Capabilities,
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
        private Capabilities $capabilities,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Capabilities $capabilities): self
    {
        return new self($capabilities);
    }

    public function read(Path $path): Read
    {
        return Read::of($this->capabilities, $path);
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
        $capabilities = $this->capabilities;

        return $this
            ->capabilities
            ->files()
            ->temporary()
            ->flatMap(
                static fn($tmp) => Write::temporary($capabilities, $tmp)
                    ->sink($chunks)
                    ->map(static fn() => Read::temporary($capabilities, $tmp)),
            );
    }
}
