<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream;

use Innmind\IO\{
    Internal\Stream,
    Internal\Watch,
    Exception\RuntimeException,
    Streams\Stream\Write,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Attempt,
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
     * @return Attempt<Stream>
     */
    public function __invoke(): Attempt
    {
        return ($this->watch)()
            ->map(static fn($ready) => $ready->toRead())
            ->flatMap(
                fn($toRead) => $toRead
                    ->find(fn($ready) => $ready === $this->stream)
                    ->keep(Instance::of(Stream::class))
                    ->match(
                        static fn($stream) => Attempt::result($stream),
                        static fn() => Attempt::error(new RuntimeException('Stream not ready')),
                    ),
            );
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
     * @internal
     * @psalm-mutation-free
     */
    public static function of(Watch $watch, Stream $stream): self
    {
        return new self($watch->forRead($stream), $stream);
    }
}
