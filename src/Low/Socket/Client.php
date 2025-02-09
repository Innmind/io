<?php
declare(strict_types = 1);

namespace Innmind\IO\Low\Socket;

use Innmind\IO\Low\Stream\{
    Readable,
    Writable,
};

interface Client extends Readable, Writable
{
}
