<?php
declare(strict_types = 1);

namespace Innmind\IO\Next;

use Innmind\IO\{
    Next\Streams\Stream,
    IO as Previous,
    Internal\Stream\Streams as Capabilities,
};

final class Streams
{
    private function __construct(
        private Previous $io,
        private Capabilities $capabilities,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Previous $io, Capabilities $capabilities): self
    {
        return new self($io, $capabilities);
    }

    /**
     * @param resource $resource
     */
    public function acquire($resource): Stream
    {
        return Stream::of(
            $this->io,
            $this->capabilities,
            $this
                ->capabilities
                ->readable()
                ->acquire($resource),
        );
    }
}
