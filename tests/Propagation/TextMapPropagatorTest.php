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

namespace Ellis\Jaeger\Tests\Propagation;

use Ellis\Jaeger\Propagation\TextMapPropagator;
use Ellis\Jaeger\SpanContext;
use Ellis\Jaeger\TraceId;
use PHPUnit\Framework\TestCase;

class TextMapPropagatorTest extends TestCase
{
    private $propagator;

    protected function setUp(): void
    {
        $this->propagator = new TextMapPropagator();
    }

    /**
     * @dataProvider dataProvideInject
     */
    public function testInject(SpanContext $ctx, array $expectedMap): void
    {
        $carrier = [];
        $this->propagator->inject($ctx, $carrier);
        $this->assertArraySubset($expectedMap, $carrier);
    }

    public function dataProvideInject(): array
    {
        return [
            'simple span context' => [
                new SpanContext(new TraceId(0, 1), 1, 0, 1),
                ['uber-trace-id' => '1:1:0:1'],
            ],
            'with 128bit trace id' => [
                new SpanContext(
                    new TraceId(4302034799541199182, 6402802542597291915),
                    6440813990460552259,
                    5333819150817783736,
                    0
                ),
                ['uber-trace-id' => '3bb3e5db6605a14e58db52fd2e7abf8b:59625e330dc47443:4a05884204ab6fb8:0'],
            ],
            'with bagagge' => [
                new SpanContext(
                    new TraceId(0, 1425326889759389850),
                    4989564328645131687,
                    4595203128530565677,
                    1,
                    ['oil-type' => 'olive', 'temperature' => '110']
                ),
                [
                    'uber-trace-id' => '13c7c74128ebb89a:453e7e5230da7da7:3fc570e622d3fa2d:1',
                    'uberctx-oil-type' => 'olive',
                    'uberctx-temperature' => '110',
                ],
            ],
        ];
    }

    public function testExtractReturnsNull(): void
    {
        $carrier = ['this' => 'isIrrelevant'];
        $ctx = $this->propagator->extract($carrier);
        $this->assertNull($ctx);
    }

    /**
     * @dataProvider dataProvideExtract
     */
    public function testExtract(
        array $carrier,
        int $expectedTraceHigh,
        int $expectedTraceLow,
        int $expectedSpanId,
        int $expectedParentId,
        int $expectedFlags,
        string $expectedDebugId,
        array $expectedBaggage
    ): void {
        $ctx = $this->propagator->extract($carrier);

        $this->assertSame($expectedTraceHigh, $ctx->getTraceId()->getHigh());
        $this->assertSame($expectedTraceLow, $ctx->getTraceId()->getLow());
        $this->assertSame($expectedSpanId, $ctx->getSpanId());
        $this->assertSame($expectedParentId, $ctx->getParentId());
        $this->assertSame($expectedFlags, $ctx->getFlags());
        $this->assertSame($expectedDebugId, $ctx->getDebugId());
        $this->assertSame($expectedBaggage, $ctx->getBaggage());
    }

    public function dataProvideExtract(): array
    {
        return [
            'with uber-trace-id only' => [
                ['uber-trace-id' => '4df16057316e6d6970a4d06c3dad3f70:7553f261422dc383:18ba4d091d384c8c:2'],
                5616376137915460969, 8116841591738285936, 8454367425040597891, 1781821304118725772, 2, '', [],
            ],
            'with debug header only' => [
                ['jaeger-debug-id' => 'hello-world'],
                0, 0, 0, 0, 0, 'hello-world', [],
            ],
            'with baggage key only' => [
                ['jaeger-baggage' => 'oil-type=sunflower,temperature=140'],
                0, 0, 0, 0, 0, '', ['oil-type' => 'sunflower', 'temperature' => '140'],
            ],
            'with baggage prefixed key only' => [
                ['uberctx-oil-type' => 'sunflower', 'uberctx-temperature' => '140'],
                0, 0, 0, 0, 0, '', ['oil-type' => 'sunflower', 'temperature' => '140'],
            ],
            'all together' => [
                [
                    'uber-trace-id' => '6b8716907cd5dd6a7818e8c14d488ebd:7452819f3412700a:495c57456a5f2ba9:1',
                    'uberctx-oil-type' => 'olive',
                    'uberctx-temperature' => '110',
                    'this' => 'isIgnored',
                ],
                7748186493739720042,
                8653922600915340989,
                8381904377263321098,
                5286196018275101609,
                1,
                '',
                ['oil-type' => 'olive', 'temperature' => '110'],
            ],
        ];
    }
}
