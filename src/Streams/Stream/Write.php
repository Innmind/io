<?php
declare(strict_types = 1);

namespace Innmind\IO\Streams\Stream;

use Innmind\IO\{
    Internal\Stream,
    Internal\Watch,
    Internal\Watch\Ready,
};
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
    SideEffect,
};

final class Write
{
    /**
     * @param callable(): bool $abort
     */
    private function __construct(
        private Watch $watch,
        private Stream $stream,
        private $abort,
        private bool $doWatch,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Watch $watch, Stream $stream): self
    {
        return new self(
            $watch->forWrite($stream),
            $stream,
            static fn() => false,
            false,
        );
    }

    /**
     * This method is called when using a heartbeat is defined to abort
     * restarting the watching of the socket. It is also used to abort when
     * sending messages (the abort is triggered before trying to send a message).
     *
     * Use this method to abort the watch when you receive signals.
     *
     * @psalm-mutation-free
     *
     * @param callable(): bool $abort
     */
    public function abortWhen(callable $abort): self
    {
        return new self(
            $this->watch,
            $this->stream,
            $abort,
            $this->doWatch,
        );
    }

    /**
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return new self(
            $this->watch->waitForever(),
            $this->stream,
            $this->abort,
            true,
        );
    }

    /**
     * @param Sequence<Str> $chunks
     *
     * @return Maybe<SideEffect>
     */
    public function sink(Sequence $chunks): Maybe
    {
        $stream = $this->stream;
        $watch = match ($this->doWatch) {
            true => $this->watch,
            false => static fn() => Maybe::just(new Ready(
                Sequence::of(),
                Sequence::of($stream),
            )),
        };
        $abort = $this->abort;

        return $chunks
            ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
            ->sink(new SideEffect)
            ->maybe(
                static fn($_, $chunk) => Maybe::just($_)
                    ->exclude(static fn() => $abort())
                    ->flatMap(static fn() => $watch())
                    ->map(static fn($ready) => $ready->toWrite())
                    ->flatMap(static fn($toWrite) => $toWrite->find(
                        static fn($ready) => $ready === $stream,
                    ))
                    ->flatMap(
                        static fn($stream) => $stream
                            ->write($chunk)
                            ->maybe(),
                    ),
            );
    }
}
