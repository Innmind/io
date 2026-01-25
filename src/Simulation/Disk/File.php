<?php
declare(strict_types = 1);

namespace Innmind\IO\Simulation\Disk;

use Innmind\IO\{
    Simulation\Disk\File\Content,
    Internal\Capabilities\Files\Implementation as Files,
};
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
final class File
{
    private function __construct(
        private Content $content,
    ) {
    }

    /**
     * @internal
     *
     * @return Attempt<self>
     */
    public static function new(Files $files): Attempt
    {
        return $files
            ->temporary()
            ->map(Content::of(...))
            ->map(static fn($content) => new self($content));
    }

    public function content(): Content
    {
        return $this->content;
    }
}
