<?php
declare(strict_types = 1);

namespace Innmind\IO\Previous;

use Innmind\IO\Internal\{
    Stream as LowLevelStream,
    Watch,
};

final class Readable
{
    private Watch $watch;

    /**
     * @psalm-mutation-free
     */
    private function __construct(Watch $watch)
    {
        $this->watch = $watch;
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function of(Watch $watch): self
    {
        return new self($watch);
    }

    /**
     * @psalm-mutation-free
     */
    public function wrap(LowLevelStream $stream): Readable\Stream
    {
        return Readable\Stream::of($this->watch, $stream);
    }
}
