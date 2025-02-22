<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Files;

use Innmind\IO\{
    Internal,
    Internal\Stream\Capabilities,
};
use Innmind\Url\Path;
use Innmind\Validation\Is;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
    SideEffect,
};

final class Write
{
    /**
     * @param \Closure(): Internal\Stream\Implementation $load
     */
    private function __construct(
        private \Closure $load,
        private bool $autoClose,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Capabilities $capabilities, Path $path): self
    {
        return new self(
            static fn() => $capabilities->writable()->open($path),
            true,
        );
    }

    /**
     * @internal
     */
    public static function temporary(Internal\Stream\Implementation $stream): self
    {
        return new self(static fn() => $stream, false);
    }

    /**
     * This is only useful in case the code is called in an asynchronous context
     * as it allows the current code to inform the event loop we're doing IO.
     *
     * Otherwise this call is useless as files are always ready to be written to.
     *
     * @psalm-mutation-free
     */
    public function watch(): self
    {
        // todo
        return $this;
    }

    /**
     * @param Sequence<Str> $chunks
     *
     * @return Maybe<SideEffect>
     */
    public function sink(Sequence $chunks): Maybe
    {
        $stream = ($this->load)();
        $autoClose = $this->autoClose;

        return $chunks
            ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
            ->sink(new SideEffect)
            ->maybe(
                static fn($_, $chunk) => $stream
                    ->write($chunk)
                    ->maybe(),
            )
            ->flatMap(static fn($sideEffect) => match ($autoClose) {
                true => $stream->close()->maybe(),
                false => Maybe::just($sideEffect),
            });
    }
}
