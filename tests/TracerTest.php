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

namespace Ellis\Jaeger\Tests;

use Ellis\Jaeger\Reporter\InMemoryReporter;
use Ellis\Jaeger\Sampler\ConstSampler;
use Ellis\Jaeger\SpanContext;
use Ellis\Jaeger\TraceId;
use Ellis\Jaeger\Tracer;
use OpenTracing\Formats;
use PHPUnit\Framework\TestCase;

class TracerTest extends TestCase
{
    private $tracer;
    private $reporter;

    protected function setUp(): void
    {
        $this->init(true);
    }

    public function testStartSpanWithChildren(): void
    {
        $rootSpan = $this->tracer->startSpan('makeDish');
        $rootCtx = $rootSpan->getContext();
        $rootSpanId = $rootCtx->getSpanId();

        $childSpan1 = $this->tracer->startSpan('fry', ['child_of' => $rootCtx]);
        $childSpan2 = $this->tracer->startSpan('fry', ['child_of' => $rootSpan]);

        $this->assertSame(0, $rootCtx->getParentId());
        $this->assertSame($rootSpanId, $childSpan1->getContext()->getParentId());
        $this->assertSame($rootSpanId, $childSpan2->getContext()->getParentId());
        $this->assertSame('fry', $childSpan1->getOperationName());
    }

    public function testStartActiveSpan(): void
    {
        $activeScope = $this->tracer->startActiveSpan('makeDish');
        $frySpan = $this->tracer->startSpan('fry');

        $activeSpan = $activeScope->getSpan();
        $activeCtx = $activeSpan->getContext();

        $this->assertSame(0, $activeCtx->getParentId());
        $this->assertSame($activeCtx->getSpanId(), $frySpan->getContext()->getParentId());
        $this->assertSame($this->tracer->getActiveSpan(), $activeSpan);

        $frySpan->finish();
        $activeScope->close();

        $cleanDishesSpan = $this->tracer->startSpan('cleanDishes');
        $this->assertSame(0, $cleanDishesSpan->getContext()->getParentId());
    }

    public function testReportWithSampled(): void
    {
        $span = $this->tracer->startSpan('hello');
        $span->finish();
        $reportedSpans = $this->reporter->getSpans();

        $this->assertContains($span, $reportedSpans);
    }

    public function testReportWithNotSampled(): void
    {
        $this->init(false);

        $span = $this->tracer->startSpan('hello');
        $span->finish();
        $reportedSpans = $this->reporter->getSpans();

        $this->assertNotContains($span, $reportedSpans);
    }

    public function testReportWithDebugId(): void
    {
        $this->init(false);

        $debugCtx = new SpanContext(new TraceId(0, 0), 0, 0, 0, [], 'test');
        $span = $this->tracer->startSpan('hello', ['child_of' => $debugCtx]);
        $span->finish();
        $reportedSpans = $this->reporter->getSpans();

        $this->assertContains($span, $reportedSpans);
    }

    public function testInject(): void
    {
        $carrier = [];
        $ctx = new SpanContext(new TraceId(0, 1), 1, 0, 1);
        $this->tracer->inject($ctx, Formats\TEXT_MAP, $carrier);

        $this->assertArrayHasKey('uber-trace-id', $carrier);
        $this->assertSame('1:1:0:1', $carrier['uber-trace-id']);
    }

    public function testExtract(): void
    {
        $ctx = $this->tracer->extract(Formats\TEXT_MAP, []);
        $this->assertNull($ctx);

        $ctx = $this->tracer->extract(Formats\TEXT_MAP, ['uber-trace-id' => '1:1:0:1']);
        $this->assertSame(0, $ctx->getTraceId()->getHigh());
        $this->assertSame(1, $ctx->getTraceId()->getLow());
        $this->assertSame(1, $ctx->getSpanId());
        $this->assertSame(0, $ctx->getParentId());
        $this->assertSame(1, $ctx->getFlags());
    }

    private function init(bool $sampled)
    {
        $sampler = new ConstSampler($sampled);
        $this->reporter = new InMemoryReporter();
        $this->tracer = new Tracer('testSvc', $this->reporter, $sampler);
    }
}
