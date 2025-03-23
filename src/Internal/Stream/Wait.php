<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

use Innmind\IO\{
    Internal\Stream,
    Internal\Watch,
    Streams\Stream\Write,
};
use Innmind\Immutable\{
    Str,
    Sequence,
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
        private Stream $stream,
    ) {
    }

    /**
     * @return Maybe<Stream>
     */
    public function __invoke(): Maybe
    {
        return ($this->watch)()
            ->maybe()
            ->map(static fn($ready) => $ready->toRead())
            ->flatMap(fn($toRead) => $toRead->find(
                fn($ready) => $ready === $this->stream,
            ))
            ->keep(Instance::of(Stream::class));
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(): Sequence<Str> $provide
     * @param callable(): bool $abort
     */
    public function withHeartbeat(
        Write $write,
        callable $provide,
        callable $abort,
    ): Wait\WithHeartbeat {
        return Wait\WithHeartbeat::of(
            $this,
            $this->stream,
            $write,
            $provide,
            $abort,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public static function of(Watch $watch, Stream $stream): self
    {
        return new self($watch->forRead($stream), $stream);
    }
}
