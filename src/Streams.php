<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\Stream\Writable;

interface Streams
{
    public function writeTo(Writable $stream): Stream\Writable;
}
