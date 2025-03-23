<?php
declare(strict_types = 1);

namespace Innmind\IO\Streams\Stream;

use Innmind\IO\{
    Internal\Stream,
    Internal\Watch,
    Internal\Watch\Ready,
    Exception\RuntimeException,
};
use Innmind\Immutable\{
    Str,
    Attempt,
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
     * @return Attempt<SideEffect>
     */
    public function sink(Sequence $chunks): Attempt
    {
        $stream = $this->stream;
        $watch = match ($this->doWatch) {
            true => $this->watch,
            false => static fn() => Attempt::result(new Ready(
                Sequence::of(),
                Sequence::of($stream),
            )),
        };
        $abort = $this->abort;

        return $chunks
            ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
            ->sink(new SideEffect)
            ->attempt(
                static fn($_, $chunk) => Attempt::result($_)
                    ->flatMap(static fn($_) => match ($abort()) {
                        true => Attempt::error(new RuntimeException('Aborted')),
                        false => Attempt::result($_),
                    })
                    ->flatMap(static fn() => $watch())
                    ->map(static fn($ready) => $ready->toWrite())
                    ->flatMap(
                        static fn($toWrite) => $toWrite
                            ->find(static fn($ready) => $ready === $stream)
                            ->match(
                                static fn($stream) => Attempt::result($stream),
                                static fn() => Attempt::error(new RuntimeException('Stream not ready to write to')),
                            ),
                    )
                    ->flatMap(static fn($stream) => $stream->write($chunk)),
            );
    }
}
