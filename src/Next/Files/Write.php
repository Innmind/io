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
    private function __construct(
        private Path $path,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Path $path): self
    {
        return new self($path);
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
        $resource = \fopen($this->path->toString(), 'w');

        if (!\is_resource($resource)) {
            /** @var Maybe<SideEffect> */
            return Maybe::nothing();
        }

        return $chunks
            ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
            ->sink(new SideEffect)
            ->maybe(
                static fn($_, $chunk) => Maybe::just(@\fwrite($resource, $chunk->toString()))
                    ->keep(Is::int()->asPredicate())
                    ->filter(static fn($written) => $written === $chunk->length())
                    ->map(static fn() => new SideEffect),
            )
            ->filter(static fn() => \fclose($resource));
    }
}
