<?php
declare(strict_types = 1);

namespace Innmind\IO\Files\Temporary;

use Innmind\IO\{
    Internal,
    Internal\Capabilities,
    Internal\Watch,
};
use Innmind\Immutable\{
    Str,
    Attempt,
    SideEffect,
};

final class Push
{
    private function __construct(
        private Internal\Stream $stream,
        private Watch $watch,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        Capabilities $capabilities,
        Internal\Stream $stream,
    ): self {
        return new self(
            $stream,
            $capabilities->watch(),
        );
    }

    /**
     * This is only useful in case the code is called in an asynchronous context
     * as it allows the current code to inform the event loop we're doing IO.
     *
     * Otherwise this call is useless as files are always ready to be read.
     *
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        return new self(
            $this->stream,
            $this->watch->waitForever(),
        );
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function chunk(Str $chunk): Attempt
    {
        $stream = $this->stream;
        $watch = $this->watch->forWrite($stream);

        return $watch()
            ->flatMap(
                static fn($ready) => $ready
                    ->toWrite()
                    ->find(static fn($ready) => $ready === $stream)
                    ->match(
                        Attempt::result(...),
                        static fn() => Attempt::error(new \RuntimeException('Stream not ready')),
                    ),
            )
            ->flatMap(static fn($stream) => $stream->write($chunk->toEncoding(Str\Encoding::ascii)));
    }
}
