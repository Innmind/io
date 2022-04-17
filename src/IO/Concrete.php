<?php
declare(strict_types = 1);

namespace Innmind\IO\IO;

use Innmind\IO\{
    IO,
    Streams,
};
use Innmind\OperatingSystem\OperatingSystem;

final class Concrete implements IO
{
    private OperatingSystem $os;

    private function __construct(OperatingSystem $os)
    {
        $this->os = $os;
    }

    public static function of(OperatingSystem $os): self
    {
        return new self($os);
    }

    public function streams(): Streams
    {
        return Streams\Concrete::of($this->os->sockets());
    }
}
