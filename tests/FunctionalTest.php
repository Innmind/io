<?php
declare(strict_types = 1);

namespace Tests\Innmind\IO;

use Innmind\IO\{
    IO,
    Readable\Frame,
    Next,
};
use Innmind\TimeContinuum\{
    ElapsedPeriod,
    Period,
};
use Innmind\IO\Internal\Socket\{
    Server,
    Client,
    Address,
};
use Innmind\IO\Internal\Stream\{
    Readable\Stream,
    Watch\Select,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Fold,
    Str,
};
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class FunctionalTest extends TestCase
{
    use BlackBox;

    public function testReadChunks()
    {
        $this
            ->forAll(Set\Elements::of(
                [1, ['f', 'o', 'o', 'b', 'a', 'r', 'b', 'a', 'z', '']],
                [2, ['fo', 'ob', 'ar', 'ba', 'z']],
                [3, ['foo', 'bar', 'baz', '']],
            ))
            ->then(function($in) {
                [$size, $expected] = $in;
                $tmp = \tmpfile();
                \fwrite($tmp, 'foobarbaz');

                $chunks = Next\IO::fromAmbientAuthority()
                    ->streams()
                    ->acquire($tmp)
                    ->read()
                    ->watch()
                    ->frames(Next\Frame::chunk($size))
                    ->lazy()
                    ->rewindable()
                    ->sequence()
                    ->map(static fn($chunk) => $chunk->toString())
                    ->toList();

                $this->assertSame($expected, $chunks);
            });
    }

    public function testReadChunksEncoding()
    {
        $tmp = \tmpfile();
        \fwrite($tmp, 'foob');

        $chunks = Next\IO::fromAmbientAuthority()
            ->streams()
            ->acquire($tmp)
            ->read()
            ->watch()
            ->toEncoding(Str\Encoding::ascii)
            ->frames(Next\Frame::chunk(1))
            ->lazy()
            ->rewindable()
            ->sequence()
            ->map(static fn($chunk) => $chunk->encoding())
            ->toList();

        $this->assertSame(
            [
                Str\Encoding::ascii,
                Str\Encoding::ascii,
                Str\Encoding::ascii,
                Str\Encoding::ascii,
                Str\Encoding::ascii,
            ],
            $chunks,
        );
    }

    public function testReadChunksWithALazySequence()
    {
        $this
            ->forAll(
                Set\Elements::of(
                    [1, 'foobarbaz', ['f', 'o', 'o', 'b', 'a', 'r', 'b', 'a', 'z', '']],
                    [2, 'foobarbaz', ['fo', 'ob', 'ar', 'ba', 'z']],
                    [3, 'foobarbaz', ['foo', 'bar', 'baz', '']],
                    [1, '', ['']],
                    [1, "\n", ["\n", '']],
                ),
                Set\Elements::of(Str\Encoding::ascii, Str\Encoding::utf8),
            )
            ->then(function($in, $encoding) {
                [$size, $content, $expected] = $in;

                $tmp = \tmpfile();
                \fwrite($tmp, $content);

                $chunks = Next\IO::fromAmbientAuthority()
                    ->streams()
                    ->acquire($tmp)
                    ->read()
                    ->toEncoding($encoding)
                    ->watch()
                    ->frames(Next\Frame::chunk($size))
                    ->lazy()
                    ->rewindable()
                    ->sequence();

                $values = $chunks
                    ->map(static fn($chunk) => $chunk->toString())
                    ->toList();
                $encodings = $chunks
                    ->map(static fn($chunk) => $chunk->encoding())
                    ->distinct()
                    ->toList();

                $this->assertSame($expected, $values);
                $this->assertSame([$encoding], $encodings);
            });
    }

    public function testReadChunksWithANonRewindableLazySequence()
    {
        $this
            ->forAll(
                Set\Elements::of(
                    [1, 'foobarbaz', ['f', 'o', 'o', 'b', 'a', 'r', 'b', 'a', 'z', '']],
                    [2, 'foobarbaz', ['fo', 'ob', 'ar', 'ba', 'z']],
                    [3, 'foobarbaz', ['foo', 'bar', 'baz', '']],
                    [1, '', ['']],
                    [1, "\n", ["\n", '']],
                ),
                Set\Elements::of(Str\Encoding::ascii, Str\Encoding::utf8),
            )
            ->then(function($in, $encoding) {
                [$size, $content, $expected] = $in;

                $tmp = \tmpfile();
                \fwrite($tmp, $content);

                $chunks = Next\IO::fromAmbientAuthority()
                    ->streams()
                    ->acquire($tmp)
                    ->read()
                    ->toEncoding($encoding)
                    ->watch()
                    ->frames(Next\Frame::chunk($size))
                    ->lazy()
                    ->rewindable()
                    ->sequence();

                $values = $chunks
                    ->map(static fn($chunk) => $chunk->toString())
                    ->toList();
                $encodings = $chunks
                    ->map(static fn($chunk) => $chunk->encoding())
                    ->distinct()
                    ->toList();

                $this->assertSame($expected, $values);
                $this->assertSame([$encoding], $encodings);
            });
    }

    public function testReadLinesWithALazySequence()
    {
        $this
            ->forAll(
                Set\Elements::of(
                    ['foobarbaz', ['foobarbaz']],
                    ["fo\nob\nar\nba\nz", ["fo\n", "ob\n", "ar\n", "ba\n", 'z']],
                    ["foo\nbar\nbaz\n", ["foo\n", "bar\n", "baz\n", '']],
                    ['', ['']],
                    ["\n", ["\n", '']],
                ),
                Set\Elements::of(Str\Encoding::ascii, Str\Encoding::utf8),
            )
            ->then(function($in, $encoding) {
                [$content, $expected] = $in;

                $tmp = \tmpfile();
                \fwrite($tmp, $content);

                $lines = Next\IO::fromAmbientAuthority()
                    ->streams()
                    ->acquire($tmp)
                    ->read()
                    ->toEncoding($encoding)
                    ->watch()
                    ->frames(Next\Frame::line())
                    ->lazy()
                    ->rewindable()
                    ->sequence();

                $values = $lines
                    ->map(static fn($line) => $line->toString())
                    ->toList();
                $encodings = $lines
                    ->map(static fn($line) => $line->encoding())
                    ->distinct()
                    ->toList();

                $this->assertSame($expected, $values);
                $this->assertSame([$encoding], $encodings);
            });
    }

    public function testReadLinesWithANonRewindableLazySequence()
    {
        $this
            ->forAll(
                Set\Elements::of(
                    ['foobarbaz', ['foobarbaz']],
                    ["fo\nob\nar\nba\nz", ["fo\n", "ob\n", "ar\n", "ba\n", 'z']],
                    ["foo\nbar\nbaz\n", ["foo\n", "bar\n", "baz\n", '']],
                    ['', ['']],
                    ["\n", ["\n", '']],
                ),
                Set\Elements::of(Str\Encoding::ascii, Str\Encoding::utf8),
            )
            ->then(function($in, $encoding) {
                [$content, $expected] = $in;

                $tmp = \tmpfile();
                \fwrite($tmp, $content);

                $lines = Next\IO::fromAmbientAuthority()
                    ->streams()
                    ->acquire($tmp)
                    ->read()
                    ->toEncoding($encoding)
                    ->watch()
                    ->frames(Next\Frame::line())
                    ->lazy()
                    ->sequence();

                $values = $lines
                    ->map(static fn($line) => $line->toString())
                    ->toList();
                $values2 = $lines
                    ->map(static fn($line) => $line->toString())
                    ->toList();

                $this->assertSame($expected, $values);
                // because we start reading from the end of the stream
                $this->assertSame([], $values2);
            });
    }

    public function testReadRealFileByLines()
    {
        $lines = Next\IO::fromAmbientAuthority()
            ->files()
            ->read(Path::of(\dirname(__DIR__).'/LICENSE'))
            ->toEncoding(Str\Encoding::ascii)
            ->watch()
            ->lines()
            ->toList();

        $this->assertCount(22, $lines);
        $this->assertSame("MIT License\n", $lines[0]->toString());
        $this->assertSame("SOFTWARE.\n", $lines[20]->toString());
        $this->assertSame('', $lines[21]->toString());
    }

    public function testSize()
    {
        $this
            ->forAll(Set\Strings::any())
            ->then(function($content) {
                $tmp = \tempnam(\sys_get_temp_dir(), 'innmind/io');
                \file_put_contents($tmp, $content);

                $size = Next\IO::fromAmbientAuthority()
                    ->files()
                    ->read(Path::of($tmp))
                    ->size()
                    ->match(
                        static fn($size) => $size->toInt(),
                        static fn() => null,
                    );

                $this->assertNotNull($size);
                $this->assertSame(\strlen($content), $size);
            });
    }

    /**
     * @todo remove as already covered by a proof in the next implementation
     */
    public function testReadFrame()
    {
        $http = <<<RAW
        POST /some-form HTTP/1.1\r
        User-Agent: Mozilla/4.0 (compatible; MSIE5.01; Windows NT)\r
        Host: innmind.com\r
        Content-Type: application/x-www-form-urlencoded\r
        Content-Length: 23\r
        Accept-Language: fr-fr\r
        Accept-Encoding: gzip, deflate\r
        Connection: Keep-Alive\r
        \r
        some[key]=value&foo=bar\r
        \r

        RAW;

        $stream = Stream::ofContent($http);
        $request = IO::of(Select::waitForever(...))
            ->readable()
            ->wrap($stream)
            ->toEncoding(Str\Encoding::ascii)
            ->watch()
            ->frames(Frame\Composite::of(
                static fn($firstLine, $headersAndBody) => [$firstLine, ...$headersAndBody],
                Frame\Line::new()->map(static fn($line) => $line->trim()->toString()),
                Frame\Sequence::of(
                    Frame\Line::new()->map(static fn($line) => $line->trim()),
                )
                    ->until(static fn($line) => $line->empty())
                    ->flatMap(
                        static fn($headers) => Frame\Chunk::of(
                            (int) $headers
                                ->toList()[3]
                                ->takeEnd(2)
                                ->toString(),
                        )
                            ->map(static fn($body) => [
                                $headers
                                    ->filter(static fn($header) => !$header->empty())
                                    ->map(static fn($header) => $header->toString())
                                    ->toList(),
                                $body->toString(),
                            ]),
                    ),
            ))
            ->one()
            ->match(
                static fn($request) => $request,
                static fn() => null,
            );

        $this->assertNotNull($request);
        $this->assertSame('POST /some-form HTTP/1.1', $request[0]);
        $this->assertSame(
            [
                'User-Agent: Mozilla/4.0 (compatible; MSIE5.01; Windows NT)',
                'Host: innmind.com',
                'Content-Type: application/x-www-form-urlencoded',
                'Content-Length: 23',
                'Accept-Language: fr-fr',
                'Accept-Encoding: gzip, deflate',
                'Connection: Keep-Alive',
            ],
            $request[1],
        );
        $this->assertSame('some[key]=value&foo=bar', $request[2]);
    }

    public function testReadFrames()
    {
        $someStream = <<<RAW
        --PAYLOAD START--
        some payload A
        --PAYLOAD END--
        --PAYLOAD START--
        some payload B
        --PAYLOAD END--
        --PAYLOAD START--
        some payload C
        --PAYLOAD END--
        RAW;

        $tmp = \tmpfile();
        \fwrite($tmp, $someStream);

        $payloads = Next\IO::fromAmbientAuthority()
            ->streams()
            ->acquire($tmp)
            ->read()
            ->toEncoding(Str\Encoding::ascii)
            ->watch()
            ->frames(
                Next\Frame::line()->flatMap(
                    static fn() => Next\Frame::line()
                        ->map(static fn($line) => $line->trim())
                        ->flatMap(
                            static fn($line) => Next\Frame::line()
                                ->map(static fn() => $line->toString()),
                        ),
                ),
            )
            ->lazy()
            ->rewindable()
            ->sequence()
            ->toList();

        $this->assertSame(
            [
                'some payload A',
                'some payload B',
                'some payload C',
            ],
            $payloads,
        );
    }

    public function testFilterOutFrame()
    {
        $someStream = <<<RAW
        --PAYLOAD START--
        some payload A
        --PAYLOAD END--
        --PAYLOAD START--
        some payload B
        --PAYLOAD END--
        --PAYLOAD START--
        some payload C
        --PAYLOAD END--
        RAW;

        $tmp = \tmpfile();
        \fwrite($tmp, $someStream);

        $payload = Next\IO::fromAmbientAuthority()
            ->streams()
            ->acquire($tmp)
            ->read()
            ->toEncoding(Str\Encoding::ascii)
            ->watch()
            ->frames(
                Next\Frame::line()->flatMap(
                    static fn() => Next\Frame::line()
                        ->map(static fn($line) => $line->trim())
                        ->filter(static fn($line) => $line->endsWith('B'))
                        ->flatMap(
                            static fn($line) => Next\Frame::line()
                                ->map(static fn() => $line->toString()),
                        ),
                ),
            )
            ->one()
            ->match(
                static fn($payload) => $payload,
                static fn() => null,
            );

        $this->assertNull($payload);
    }

    public function testReadingSequenceOfLineFrames()
    {
        $someStream = <<<RAW
        --PAYLOAD START--
        some payload A
        --PAYLOAD END--

        RAW;

        $tmp = \tmpfile();
        \fwrite($tmp, $someStream);

        $payload = Next\IO::fromAmbientAuthority()
            ->streams()
            ->acquire($tmp)
            ->read()
            ->toEncoding(Str\Encoding::ascii)
            ->watch()
            ->frames(
                Next\Frame::sequence(Next\Frame::line())->map(
                    static fn($lines) => $lines
                        ->map(static fn($line) => $line->match(
                            static fn($line) => $line,
                            static fn() => throw new \Exception,
                        ))
                        ->takeWhile(static fn($line) => !$line->empty()),
                ),
            )
            ->one()
            ->match(
                static fn($payload) => $payload,
                static fn() => null,
            );

        $this->assertNotNull($payload);
        $this->assertSame(
            [
                "--PAYLOAD START--\n",
                "some payload A\n",
                "--PAYLOAD END--\n",
            ],
            $payload
                ->map(static fn($line) => $line->toString())
                ->toList(),
        );
    }

    public function testSocketClientSend()
    {
        @\unlink('/tmp/foo.sock');
        $sockets = Next\IO::fromAmbientAuthority()->sockets();
        $address = Next\Sockets\Unix\Address::of(Path::of('/tmp/foo'));

        $server = $sockets
            ->servers()
            ->takeOver($address)
            ->match(
                static fn($server) => $server,
                static fn() => null,
            );

        $this->assertNotNull($server);

        $client = $sockets
            ->clients()
            ->unix($address)
            ->match(
                static fn($server) => $server,
                static fn() => null,
            );

        $this->assertNotNull($client);

        $this->assertTrue(
            $client
                ->watch()
                ->toEncoding(Str\Encoding::ascii)
                ->sink(Sequence::of(
                    Str::of("GET / HTTP/1.1\n"),
                    Str::of("Host: example.com\n"),
                    Str::of("\n"),
                ))
                ->match(
                    static fn() => true,
                    static fn() => false,
                ),
        );

        $this->assertSame(
            <<<HTTP
            GET / HTTP/1.1
            Host: example.com


            HTTP,
            $server
                ->accept()
                ->flatMap(
                    static fn($client) => $client
                        ->watch()
                        ->frames(Next\Frame::chunk(34))
                        ->one()
                )
                ->match(
                    static fn($chunk) => $chunk->toString(),
                    static fn() => null,
                ),
        );

        $client->close()->memoize();
        $server->close()->memoize();
    }

    public function testSocketClientHeartbeatWithSocketClosing()
    {
        @\unlink('/tmp/foo.sock');
        $sockets = Next\IO::fromAmbientAuthority()->sockets();
        $address = Next\Sockets\Unix\Address::of(Path::of('/tmp/foo'));

        $server = $sockets
            ->servers()
            ->takeOver($address)
            ->match(
                static fn($server) => $server,
                static fn() => null,
            );

        $this->assertNotNull($server);

        $client = $sockets
            ->clients()
            ->unix($address)
            ->match(
                static fn($client) => $client,
                static fn() => null,
            );

        $this->assertNotNull($client);

        $heartbeats = 0;
        $result = $client
            ->timeoutAfter(Period::millisecond(500))
            ->toEncoding(Str\Encoding::ascii)
            ->heartbeatWith(function() use (&$heartbeats, $server, $client) {
                if ($heartbeats === 0) {
                    ++$heartbeats;

                    return Sequence::of(Str::of('foo'));
                }

                if ($heartbeats === 1) {
                    ++$heartbeats;
                    $this->assertSame(
                        'foo',
                        $server
                            ->accept()
                            ->flatMap(
                                static fn($client) => $client
                                    ->frames(Next\Frame::chunk(3))
                                    ->one(),
                            )
                            ->match(
                                static fn($data) => $data->toString(),
                                static fn() => null,
                            ),
                    );
                    $client->close()->memoize();
                }

                return Sequence::of();
            })
            // todo remove the filter if it's implemented in Next\Frame\Chunk
            ->frames(Next\Frame::chunk(1)->filter(
                static fn($chunk) => $chunk->length() === 1,
            ))
            ->one()
            ->map(dump(...))
            ->match(
                static fn() => true,
                static fn() => false,
            );

        $this->assertSame(2, $heartbeats);
        $this->assertFalse($result, 'It should fail due to the closing of the socket');
        $server->close();
    }

    public function testSocketClientHeartbeat()
    {
        @\unlink('/tmp/foo.sock');
        $sockets = Next\IO::fromAmbientAuthority()->sockets();
        $address = Next\Sockets\Unix\Address::of(Path::of('/tmp/foo'));

        $server = $sockets
            ->servers()
            ->takeOver($address)
            ->match(
                static fn($server) => $server,
                static fn() => null,
            );

        $this->assertNotNull($server);

        $client = $sockets
            ->clients()
            ->unix($address)
            ->match(
                static fn($client) => $client,
                static fn() => null,
            );

        $this->assertNotNull($client);

        $heartbeats = 0;
        $result = $client
            ->timeoutAfter(Period::millisecond(500))
            ->toEncoding(Str\Encoding::ascii)
            ->heartbeatWith(function() use (&$heartbeats, $server) {
                if ($heartbeats === 0) {
                    ++$heartbeats;

                    return Sequence::of(Str::of('foo'));
                }

                if ($heartbeats === 1) {
                    ++$heartbeats;
                    $this->assertSame(
                        'foo',
                        $server
                            ->accept()
                            ->flatMap(
                                static fn($client) => $client
                                    ->sink(Sequence::of(Str::of('bar')))
                                    ->map(static fn() => $client),
                            )
                            ->flatMap(
                                static fn($client) => $client
                                    ->frames(Next\Frame::chunk(3))
                                    ->one(),
                            )
                            ->match(
                                static fn($data) => $data->toString(),
                                static fn() => null,
                            ),
                    );
                }

                return Sequence::of();
            })
            ->frames(Next\Frame::chunk(3))
            ->one()
            ->match(
                static fn($response) => $response->toString(),
                static fn() => null,
            );

        $this->assertSame(2, $heartbeats);
        $this->assertSame('bar', $result);
        $client->close()->memoize();
        $server->close()->memoize();
    }

    public function testSocketAbort()
    {
        @\unlink('/tmp/foo.sock');
        $sockets = Next\IO::fromAmbientAuthority()->sockets();
        $address = Next\Sockets\Unix\Address::of(Path::of('/tmp/foo'));

        $server = $sockets
            ->servers()
            ->takeOver($address)
            ->match(
                static fn($server) => $server,
                static fn() => null,
            );

        $this->assertNotNull($server);

        $client = $sockets
            ->clients()
            ->unix($address)
            ->match(
                static fn($client) => $client,
                static fn() => null,
            );

        $this->assertNotNull($client);
        $clientFromServerSide = null;

        $heartbeats = 0;
        $result = $client
            ->timeoutAfter(Period::millisecond(500))
            ->toEncoding(Str\Encoding::ascii)
            ->heartbeatWith(function() use (&$heartbeats, $server, &$clientFromServerSide) {
                if ($heartbeats === 1) {
                    $clientFromServerSide = $server->accept()->match(
                        static fn($client) => $client,
                        static fn() => null,
                    );
                }

                if ($clientFromServerSide) {
                    $this->assertSame(
                        'foo',
                        $clientFromServerSide
                            ->frames(Next\Frame::chunk(3))
                            ->one()
                            ->match(
                                static fn($data) => $data->toString(),
                                static fn() => null,
                            ),
                    );
                }

                ++$heartbeats;

                return Sequence::of(Str::of('foo'));
            })
            ->abortWhen(static function() use (&$heartbeats) {
                return $heartbeats > 2;
            })
            ->frames(Next\Frame::chunk(3))
            ->one()
            ->match(
                static fn($response) => $response->toString(),
                static fn() => null,
            );

        $this->assertSame(3, $heartbeats);
        $this->assertNull($result);
        $client->close()->memoize();
        $server->close()->memoize();
    }

    public function testServerAcceptConnection()
    {
        @\unlink('/tmp/foo.sock');
        $sockets = Next\IO::fromAmbientAuthority()->sockets();
        $address = Next\Sockets\Unix\Address::of(Path::of('/tmp/foo'));

        $server = $sockets
            ->servers()
            ->takeOver($address)
            ->match(
                static fn($server) => $server,
                static fn() => null,
            );

        $this->assertNotNull($server);

        $client = $sockets
            ->clients()
            ->unix($address)
            ->match(
                static fn($client) => $client,
                static fn() => null,
            );

        $this->assertNotNull($client);

        $_ = $client
            ->sink(Sequence::of(Str::of('foo')))
            ->match(
                static fn() => null,
                static fn() => null,
            );

        $result = $server
            ->timeoutAfter(Period::second(1))
            ->accept()
            ->flatMap(
                static fn($client) => $client
                    ->frames(Next\Frame::chunk(3))
                    ->one(),
            )
            ->match(
                static fn($data) => $data->toString(),
                static fn() => null,
            );

        $this->assertSame('foo', $result);
        $client->close()->memoize();
        $server->close()->memoize();
    }

    public function testServerPool()
    {
        @\unlink('/tmp/foo.sock');
        @\unlink('/tmp/bar.sock');
        @\unlink('/tmp/baz.sock');
        $sockets = Next\IO::fromAmbientAuthority()->sockets();
        $addressFoo = Next\Sockets\Unix\Address::of(Path::of('/tmp/foo'));
        $addressBar = Next\Sockets\Unix\Address::of(Path::of('/tmp/bar'));
        $addressBaz = Next\Sockets\Unix\Address::of(Path::of('/tmp/baz'));
        $serverFoo = $sockets
            ->servers()
            ->takeOver($addressFoo)
            ->match(
                static fn($server) => $server,
                static fn() => null,
            );

        $this->assertNotNull($serverFoo);

        $serverBar = $sockets
            ->servers()
            ->takeOver($addressBar)
            ->match(
                static fn($server) => $server,
                static fn() => null,
            );

        $this->assertNotNull($serverBar);

        $serverBaz = $sockets
            ->servers()
            ->takeOver($addressBaz)
            ->match(
                static fn($server) => $server,
                static fn() => null,
            );

        $this->assertNotNull($serverBaz);

        $clientFoo = $sockets
            ->clients()
            ->unix($addressFoo)
            ->match(
                static fn($socket) => $socket,
                static fn() => null,
            );

        $this->assertNotNull($clientFoo);

        $clientBar = $sockets
            ->clients()
            ->unix($addressBar)
            ->match(
                static fn($socket) => $socket,
                static fn() => null,
            );

        $this->assertNotNull($clientBar);

        $clientBaz = $sockets
            ->clients()
            ->unix($addressBaz)
            ->match(
                static fn($socket) => $socket,
                static fn() => null,
            );

        $this->assertNotNull($clientBaz);

        $_ = $clientFoo
            ->sink(Sequence::of(Str::of('foo')))
            ->match(
                static fn() => null,
                static fn() => null,
            );
        $_ = $clientBar
            ->sink(Sequence::of(Str::of('bar')))
            ->match(
                static fn() => null,
                static fn() => null,
            );
        $_ = $clientBaz
            ->sink(Sequence::of(Str::of('baz')))
            ->match(
                static fn() => null,
                static fn() => null,
            );

        $servers = IO::of(Select::timeoutAfter(...))
            ->sockets()
            ->servers();
        $result = $serverFoo
            ->timeoutAfter(Period::second(1))
            ->pool($serverBar)
            ->with($serverBaz)
            ->accept()
            ->flatMap(
                static fn($client) => $client
                    ->frames(Next\Frame::chunk(3))
                    ->one()
                    ->toSequence(),
            )
            ->map(static fn($data) => $data->toString());

        $this->assertCount(3, $result);
        $this->assertTrue($result->contains('foo'));
        $this->assertTrue($result->contains('bar'));
        $this->assertTrue($result->contains('baz'));
        $clientFoo->close()->memoize();
        $clientBar->close()->memoize();
        $clientBaz->close()->memoize();
        $serverFoo->close()->memoize();
        $serverBar->close()->memoize();
        $serverBaz->close()->memoize();
    }
}
