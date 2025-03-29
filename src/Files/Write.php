<?php
declare(strict_types = 1);

namespace Innmind\IO\Files;

use Innmind\IO\{
    Internal,
    Internal\Capabilities,
    Internal\Watch,
    Internal\Watch\Ready,
    Exception\RuntimeException,
};
use Innmind\Url\Path;
use Innmind\Validation\Is;
use Innmind\Immutable\{
    Str,
    Attempt,
    Sequence,
    SideEffect,
};

final class Write
{
    /**
     * @param \Closure(): Internal\Stream $load
     */
    private function __construct(
        private Watch $watch,
        private \Closure $load,
        private bool $autoClose,
        private bool $doWatch,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Capabilities $capabilities, Path $path): self
    {
        return new self(
            $capabilities->watch(),
            static fn() => $capabilities
                ->files()
                ->write($path)
                ->match(
                    static fn($stream) => $stream,
                    static fn() => throw new \RuntimeException('Failed to open file'),
                ),
            true,
            false,
        );
    }

    /**
     * @internal
     */
    public static function temporary(
        Capabilities $capabilities,
        Internal\Stream $stream,
    ): self {
        return new self(
            $capabilities->watch(),
            static fn() => $stream,
            false,
            false,
        );
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
        return new self(
            $this->watch,
            $this->load,
            $this->autoClose,
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
        $stream = ($this->load)();
        $autoClose = $this->autoClose;
        $watch = match ($this->doWatch) {
            true => $this->watch->forWrite($stream),
            false => static fn() => Attempt::result(new Ready(
                Sequence::of(),
                Sequence::of($stream),
            )),
        };

        return $chunks
            ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
            ->sink(new SideEffect)
            ->attempt(
                static fn($_, $chunk) => $watch()
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
            )
            ->flatMap(static fn() => $stream->sync())
            ->recover(static function($e) use ($stream, $autoClose) {
                if ($autoClose) {
                    $_ = $stream->close()->memoize();
                }

                return Attempt::error($e);
            })
            ->flatMap(static fn($sideEffect) => match ($autoClose) {
                true => $stream->close(),
                false => Attempt::result($sideEffect),
            });
    }
}
