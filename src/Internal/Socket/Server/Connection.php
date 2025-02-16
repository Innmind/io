<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket\Server;

use Innmind\IO\Internal\Stream\{
    Readable,
    Writable,
};

interface Connection extends Readable, Writable
{
}
