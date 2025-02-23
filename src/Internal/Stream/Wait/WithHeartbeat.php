<?php
declare(strict_types = 1);

namespace Innmind\IO\Internal\Stream\Wait;

use Innmind\IO\Internal\{
    Stream,
    Stream\Wait,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Maybe,
    SideEffect,
};

/**
 * @internal
 */
final class WithHeartbeat
{
    /**
     * @psalm-mutation-free
     *
     * @param callable(Sequence<Str>): Maybe<SideEffect> $send
     * @param callable(): Sequence<Str> $provide
     * @param callable(): bool $abort
     */
    private function __construct(
        private Wait $wait,
        private $send,
        private $provide,
        private $abort,
    ) {
    }

    /**
     * @return Maybe<Stream>
     */
    public function __invoke(Stream $stream): Maybe
    {
        do {
            $ready = ($this->wait)($stream);
            $socketReadable = $ready->match(
                static fn() => true,
                static fn() => false,
            );

            if ($socketReadable) {
                return $ready;
            }

            $sent = ($this->send)(($this->provide)())->match(
                static fn() => true,
                static fn() => false,
            );

            if (!$sent) {
                /** @var Maybe<Stream> */
                return Maybe::nothing();
            }
        } while (!($this->abort)() && !$stream->closed());

        /** @var Maybe<Stream> */
        return Maybe::nothing();
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(Sequence<Str>): Maybe<SideEffect> $send
     * @param callable(): Sequence<Str> $provide
     * @param callable(): bool $abort
     */
    public static function of(
        Wait $wait,
        callable $send,
        callable $provide,
        callable $abort,
    ): self {
        return new self($wait, $send, $provide, $abort);
    }
}
