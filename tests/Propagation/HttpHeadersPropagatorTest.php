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

use Ellis\Jaeger\Propagation\HttpHeadersPropagator;
use Ellis\Jaeger\Propagation\TextMapPropagator;
use Ellis\Jaeger\SpanContext;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;

class HttpHeadersPropagatorTest extends TestCase
{
    public function testInject(): void
    {
        $propagator = new HttpHeadersPropagator(new TextMapPropagatorStub());
        $ctx = $this->getMockBuilder(SpanContext::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $carrier = $this->getMockBuilder(MessageInterface::class)->getMock();
        $carrier->expects($this->exactly(3))
            ->method('withHeader')
            ->withConsecutive(
                [$this->equalTo('uber-trace-id'), $this->equalTo('1:1:0:1')],
                [$this->equalTo('uberctx-oil-type'), $this->equalTo('olive')],
                [$this->equalTo('uberctx-temperature'), $this->equalTo('120')]
            )
            ->willReturn($carrier)
        ;

        $propagator->inject($ctx, $carrier);
    }

    public function testExtract(): void
    {
        $carrier = $this->getMockBuilder(MessageInterface::class)->getMock();
        $carrier->expects($this->any())
            ->method('getHeaders')
            ->willReturn([
                'uber-trace-id' => ['1:1:0:1'],
                'uberctx-oil-type' => ['olive'],
                'uberctx-temperature' => ['110', '120'],
            ])
        ;

        $ctx = $this->getMockBuilder(SpanContext::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $textMapPropagator = $this->getMockBuilder(TextMapPropagator::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $textMapPropagator->expects($this->atLeastOnce())
            ->method('extract')
            ->with($this->equalTo([
                'uber-trace-id' => '1:1:0:1',
                'uberctx-oil-type' => 'olive',
                'uberctx-temperature' => '120',
            ]))
            ->willReturn($ctx)
        ;

        $propagator = new HttpHeadersPropagator($textMapPropagator);
        $actualCtx = $propagator->extract($carrier);

        $this->assertSame($actualCtx, $ctx);
    }
}
