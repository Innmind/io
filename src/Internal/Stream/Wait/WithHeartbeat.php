<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Wait;

use Innmind\IO\{
    Internal\Stream,
    Internal\Stream\Wait,
    Streams\Stream\Write,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Maybe,
};

/**
 * @internal
 */
final class WithHeartbeat
{
    /**
     * @psalm-mutation-free
     *
     * @param callable(): Sequence<Str> $provide
     * @param callable(): bool $abort
     */
    private function __construct(
        private Wait $wait,
        private Stream $stream,
        private Write $write,
        private $provide,
        private $abort,
    ) {
    }

    /**
     * @return Maybe<Stream>
     */
    public function __invoke(): Maybe
    {
        do {
            $ready = ($this->wait)();
            $socketReadable = $ready->match(
                static fn() => true,
                static fn() => false,
            );

            if ($socketReadable) {
                return $ready;
            }

            $sent = $this->write->sink(($this->provide)())->match(
                static fn() => true,
                static fn() => false,
            );

            if (!$sent) {
                /** @var Maybe<Stream> */
                return Maybe::nothing();
            }
        } while (!($this->abort)() && !$this->stream->closed());

        /** @var Maybe<Stream> */
        return Maybe::nothing();
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(): Sequence<Str> $provide
     * @param callable(): bool $abort
     */
    public static function of(
        Wait $wait,
        Stream $stream,
        Write $write,
        callable $provide,
        callable $abort,
    ): self {
        return new self($wait, $stream, $write, $provide, $abort);
    }
}
