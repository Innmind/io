<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Socket;

use Innmind\IO\Internal\Stream\{
    Readable,
    Writable,
};

interface Client extends Readable, Writable
{
}
