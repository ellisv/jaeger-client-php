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

use Ellis\Jaeger\Span;
use Ellis\Jaeger\SpanContext;
use Ellis\Jaeger\Tracer;
use PHPUnit\Framework\TestCase;

class SpanTest extends TestCase
{
    /**
     * @dataProvider dataProvideFinish
     */
    public function testFinish(int $currentTime, int $startTime, ?int $finishTime, int $expectedDuration): void
    {
        $tracer = $this->createMock(Tracer::class);
        $tracer->method('currentTime')
            ->willReturn($currentTime)
        ;
        $context = $this->createMock(SpanContext::class);

        $span = new Span($tracer, 'test', $context, $startTime, [], []);

        $tracer->expects($this->once())
            ->method('reportSpan')
            ->with($this->equalTo($span))
        ;

        $span->finish($finishTime);

        $this->assertSame($expectedDuration, $span->getDuration());
    }

    public function dataProvideFinish(): array
    {
        return [
            'without finish time' => [15, 10, null, 5],
            'with finish time' => [400, 350, 370, 20],
        ];
    }
}
