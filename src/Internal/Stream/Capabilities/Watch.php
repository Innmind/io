<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Capabilities;

use Innmind\IO\Internal\Stream\Watch as WatchInterface;
use Innmind\TimeContinuum\ElapsedPeriod;

interface Watch
{
    public function timeoutAfter(ElapsedPeriod $timeout): WatchInterface;
    public function waitForever(): WatchInterface;
}
