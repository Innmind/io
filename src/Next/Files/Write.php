<?php
declare(strict_types = 1);

namespace Innmind\IO\Next\Files;

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
     * @param \Closure(): (false|resource) $load
     */
    private function __construct(
        private \Closure $load,
        private bool $autoClose,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Path $path): self
    {
        return new self(
            static fn() => \fopen($path->toString(), 'w'),
            true,
        );
    }

    /**
     * @internal
     *
     * @param resource $resource
     */
    public static function temporary($resource): self
    {
        return new self(static fn() => $resource, false);
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
        $resource = ($this->load)();

        if (!\is_resource($resource)) {
            /** @var Maybe<SideEffect> */
            return Maybe::nothing();
        }

        $close = match ($this->autoClose) {
            true => static fn() => \fclose($resource),
            false => static fn() => true,
        };

        return $chunks
            ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
            ->sink(new SideEffect)
            ->maybe(
                static fn($_, $chunk) => Maybe::just(@\fwrite($resource, $chunk->toString()))
                    ->keep(Is::int()->asPredicate())
                    ->filter(static fn($written) => $written === $chunk->length())
                    ->map(static fn() => new SideEffect),
            )
            ->filter($close);
    }
}
