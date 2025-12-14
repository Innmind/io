<?php
declare(strict_types = 1);

namespace Innmind\IO\Simulation\Disk;

use Innmind\IO\Simulation\Disk\File\Content;

/**
 * @internal
 */
final class File
{
    private function __construct(
        private Content $content,
    ) {
    }

    public function content(): Content
    {
        return $this->content;
    }
}
