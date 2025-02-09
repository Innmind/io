<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Stream\Capabilities;

use Innmind\IO\Low\Stream\{
    Bidirectional,
};

interface Temporary
{
    public function new(): Bidirectional;
}
