<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\OperatingSystem\OperatingSystem;

final class Factory
{
    public static function build(OperatingSystem $os): IO
    {
        return IO\Concrete::of($os);
    }
}
