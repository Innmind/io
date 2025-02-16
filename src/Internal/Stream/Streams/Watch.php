<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Streams;

use Innmind\IO\Internal\Stream\{
    Capabilities,
    Watch as WatchInterface,
    Watch\Select,
};
use Innmind\TimeContinuum\ElapsedPeriod;

final class Watch implements Capabilities\Watch
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

    public function timeoutAfter(ElapsedPeriod $timeout): WatchInterface
    {
        return Select::timeoutAfter($timeout);
    }

    public function waitForever(): WatchInterface
    {
        return Select::waitForever();
    }
}
