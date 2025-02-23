<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

use Innmind\IO\Internal\{
    Stream,
    Watch,
};
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
};

/**
 * @internal
 */
final class Wait
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private Watch $watch,
    ) {
    }

    /**
     * @return Maybe<Stream>
     */
    public function __invoke(Stream $socket): Maybe
    {
        return $this
            ->watch
            ->forRead($socket)()
            ->map(static fn($ready) => $ready->toRead())
            ->flatMap(static fn($toRead) => $toRead->find(
                static fn($ready) => $ready === $socket,
            ))
            ->keep(Instance::of(Stream::class));
    }

    /**
     * @psalm-mutation-free
     */
    public static function of(Watch $watch): self
    {
        return new self($watch);
    }
}
