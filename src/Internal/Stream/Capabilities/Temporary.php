<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Capabilities;

use Innmind\IO\Internal\Stream\{
    Bidirectional,
};

interface Temporary
{
    public function new(): Bidirectional;
}
