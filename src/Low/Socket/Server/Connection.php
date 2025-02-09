<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Socket\Server;

use Innmind\IO\Low\Stream\{
    Readable,
    Writable,
};

interface Connection extends Readable, Writable
{
}
