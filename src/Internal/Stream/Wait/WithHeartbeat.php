<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Wait;

use Innmind\IO\{
    Internal\Stream,
    Internal\Stream\Wait,
    Exception\RuntimeException,
    Streams\Stream\Write,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Attempt,
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
     * @return Attempt<Stream>
     */
    public function __invoke(): Attempt
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

            $error = $this->write->sink(($this->provide)())->match(
                static fn() => null,
                static fn($e) => $e,
            );

            if ($error instanceof \Throwable) {
                /** @var Attempt<Stream> */
                return Attempt::error($error);
            }
        } while (!($this->abort)() && !$this->stream->closed());

        /** @var Attempt<Stream> */
        return Attempt::error(new RuntimeException('Watch aborted or stream closed'));
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
