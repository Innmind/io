<?php
declare(strict_types = 1);

namespace Innmind\IO;

use Innmind\TimeContinuum\ElapsedPeriod;
use Innmind\Stream\Watch;

final class IO
{
    /** @var callable(?ElapsedPeriod): Watch */
    private $watch;

    /**
     * @param callable(?ElapsedPeriod): Watch $watch
     */
    private function __construct(callable $watch)
    {
        $this->watch = $watch;
    }

    /**
     * @param callable(?ElapsedPeriod): Watch $watch
     */
    public static function of(callable $watch): self
    {
        return new self($watch);
    }

    public function readable(): Readable
    {
        return Readable::of($this->watch);
    }
}
