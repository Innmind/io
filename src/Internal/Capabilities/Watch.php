<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Capabilities;

use Innmind\IO\Internal\{
    Watch as WatchInterface,
    Watch\Select,
};
use Innmind\TimeContinuum\Period;

final class Watch
{
    private function __construct()
    {
    }

    /**
     * @internal
     */
    public static function of(): self
    {
        return new self;
    }

    public function timeoutAfter(Period $timeout): WatchInterface
    {
        return Select::new()->timeoutAfter($timeout);
    }

    public function waitForever(): WatchInterface
    {
        return Select::new();
    }
}
