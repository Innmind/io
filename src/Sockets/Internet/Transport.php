<?php
declare(strict_types = 1);

namespace Innmind\IO\Sockets\Internet;

use Innmind\IO\Exception\TransportNotSupportedByTheSystem;
use Innmind\Immutable\Map;

final class Transport
{
    private string $transport;
    /** @var Map<string, int|bool|float|string|array> */
    private Map $options;

    private function __construct(string $transport)
    {
        $allowed = \stream_get_transports();

        if (!\in_array($transport, $allowed, true)) {
            throw new TransportNotSupportedByTheSystem($transport, ...$allowed);
        }

        $this->transport = $transport;
        /** @var Map<string, int|bool|float|string|array> */
        $this->options = Map::of();
    }

    #[\NoDiscard]
    public static function tcp(): self
    {
        return new self('tcp');
    }

    #[\NoDiscard]
    public static function ssl(): self
    {
        return new self('ssl');
    }

    #[\NoDiscard]
    public static function sslv3(): self
    {
        return new self('sslv3');
    }

    #[\NoDiscard]
    public static function sslv2(): self
    {
        return new self('sslv2');
    }

    #[\NoDiscard]
    public static function tls(): self
    {
        return new self('tls');
    }

    #[\NoDiscard]
    public static function tlsv10(): self
    {
        return new self('tlsv1.0');
    }

    #[\NoDiscard]
    public static function tlsv11(): self
    {
        return new self('tlsv1.1');
    }

    #[\NoDiscard]
    public static function tlsv12(): self
    {
        return new self('tlsv1.2');
    }

    /**
     * @psalm-mutation-free
     */
    #[\NoDiscard]
    public function withOption(string $key, int|bool|float|string|array $value): self
    {
        $self = clone $this;
        $self->options = ($this->options)($key, $value);

        return $self;
    }

    /**
     * @return Map<string, int|bool|float|string|array>
     */
    #[\NoDiscard]
    public function options(): Map
    {
        return $this->options;
    }

    #[\NoDiscard]
    public function toString(): string
    {
        return $this->transport;
    }
}
