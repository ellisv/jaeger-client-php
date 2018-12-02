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

namespace Ellis\Jaeger\Tests\Transport;

use Ellis\Jaeger\Thrift\TResettableMemoryBuffer;
use Ellis\Jaeger\Transport\UdpTransport;
use Jaeger\Thrift\Agent\AgentClient;
use Jaeger\Thrift\Batch;
use Jaeger\Thrift\Process;
use Jaeger\Thrift\Span;
use PHPUnit\Framework\TestCase;
use Thrift\Protocol\TCompactProtocol;

class UdpTransportTest extends TestCase
{
    public function testEmitBatchOverhead(): void
    {
        $buffer = new TResettableMemoryBuffer();
        $protocol = new TCompactProtocol($buffer);

        $process = new Process(['serviceName' => 'svcName']);
        $span = new Span([
            'traceIdLow' => 2138914532489608643,
            'traceIdHigh' => 8226669872089855603,
            'spanId' => 3019328212899340692,
            'parentSpanId' => 1305599373459258033,
            'operationName' => 'operationName',
            'flags' => 0b11,
            'startTime' => 1543157893747360,
            'duration' => 100000,
        ]);

        $process->write($protocol);
        $span->write($protocol);
        $nakedLength = $buffer->available();

        $buffer->reset();

        $client = new AgentClient($protocol);
        $client->emitBatch(new Batch(['process' => $process, 'spans' => [$span]]));

        $envelopedLength = $buffer->available();

        $this->assertLessThan(UdpTransport::EMIT_BATCH_OVERHEAD, $envelopedLength - $nakedLength);
    }
}
