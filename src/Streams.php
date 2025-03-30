<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\IO\{
    Streams\Stream,
    Internal\Capabilities,
};

final class Streams
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

    /**
     * @param resource $resource
     */
    public function acquire($resource): Stream
    {
        return Stream::of(
            $this->capabilities,
            $this
                ->capabilities
                ->streams()
                ->acquire($resource),
        );
    }
}
