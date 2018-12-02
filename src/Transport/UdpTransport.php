<?php

/*
 * This file is part of the Jaeger Client package.
 *
 * (c) Eligijus Vitkauskas <eligijusvitkauskas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Ellis\Jaeger\Transport;

use Ellis\Jaeger\Span;
use Ellis\Jaeger\Thrift\ThriftFactory;
use Ellis\Jaeger\Thrift\ThriftSizeCalculator;
use Ellis\Jaeger\Thrift\TSocket;
use Jaeger\Thrift\Agent\AgentClient;
use Jaeger\Thrift\Batch;
use Thrift\Exception\TException;
use Thrift\Factory\TCompactProtocolFactory;
use Thrift\Transport\TBufferedTransport;

class UdpTransport implements TransportInterface
{
    /**
     * @var int Default max size of UDP packet we want to send, this is synced
     *          with jaeger-agent
     */
    const DEFAULT_MAX_PACKET_SIZE = 65000;

    const EMIT_BATCH_OVERHEAD = 33;

    private $transport;
    private $client;
    private $sizeCalculator;

    private $bufferSize = 0;
    private $maxBufferSize;
    private $spans = [];
    private $process;
    private $processSize = 0;

    public function __construct(string $host, int $port, int $maxPacketSize = self::DEFAULT_MAX_PACKET_SIZE)
    {
        $socket = new TSocket(sprintf('udp://%s', $host), $port, $maxPacketSize);
        $this->transport = new TBufferedTransport($socket, $maxPacketSize, $maxPacketSize);

        $protocolFactory = new TCompactProtocolFactory();
        $protocol = $protocolFactory->getProtocol($this->transport);

        $this->client = new AgentClient($protocol);
        $this->sizeCalculator = new ThriftSizeCalculator($protocolFactory);
        $this->maxBufferSize = $maxPacketSize - static::EMIT_BATCH_OVERHEAD;
    }

    /**
     * {@inheritdoc}
     */
    public function append(Span $span): int
    {
        if ($this->process === null) {
            $this->process = ThriftFactory::buildProcess($span);
            $this->processSize = $this->sizeCalculator->size($this->process);
            $this->bufferSize += $this->processSize;
        }

        $tSpan = ThriftFactory::buildSpan($span);
        $size = $this->sizeCalculator->size($tSpan);

        if ($size + $this->processSize > $this->maxBufferSize) {
            throw new TransportException(sprintf(
                'Received a span that is too large, size = %d, max = %d',
                $size,
                $this->maxBufferSize - $this->processSize
            ), null, 1);
        }

        $flushed = 0;
        if ($this->bufferSize + $size > $this->maxBufferSize) {
            $flushed = $this->flush();
        }

        $this->bufferSize += $size;
        $this->spans[] = $tSpan;

        return $flushed;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): int
    {
        $count = \count($this->spans);
        if ($count === 0) {
            return 0;
        }

        if (!$this->transport->isOpen()) {
            try {
                $this->transport->open();
            } catch (TException $e) {
                $this->resetBuffer();

                throw new TransportException('Failed to open thrift transport', $e, $count);
            }
        }

        $batch = new Batch(['process' => $this->process, 'spans' => $this->spans]);

        try {
            $this->client->emitBatch($batch);
        } catch (TException $e) {
            throw new TransportException(sprintf('Failed to send a batch: %s', $e->getMessage()), $e, $count);
        } finally {
            $this->resetBuffer();
        }

        return $count;
    }

    public function close(): void
    {
        if ($this->transport->isOpen()) {
            $this->transport->close();
        }
    }

    private function resetBuffer(): void
    {
        $this->spans = [];
        $this->bufferSize = $this->processSize;
    }
}
