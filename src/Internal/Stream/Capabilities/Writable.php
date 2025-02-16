<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Capabilities;

use Innmind\IO\Internal\Stream\Writable as Write;
use Innmind\Url\Path;

interface Writable
{
    public function open(Path $path): Write;

    /**
     * @param resource $resource
     */
    public function acquire($resource): Write;
}
