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

use Ellis\Jaeger\SpanContext;
use Ellis\Jaeger\TraceId;
use PHPUnit\Framework\TestCase;

class SpanContextTest extends TestCase
{
    public function testGetBaggageItem(): void
    {
        $traceId = $this->createMock(TraceId::class);
        $context = new SpanContext($traceId, 0, 0, 0, []);
        $nonExistingItem = $context->getBaggageItem('nonexisting');

        $this->assertNull($nonExistingItem);

        $context = $context->withBaggageItem('testitem', 'hello');
        $existingItem = $context->getBaggageItem('testitem');

        $this->assertSame('hello', $existingItem);
    }

    public function testWithBaggageItemNonSameInstance(): void
    {
        $traceId = $this->createMock(TraceId::class);
        $context = new SpanContext($traceId, 0, 0, 0, []);
        $otherContext = $context->withBaggageItem('testitem', 'hello');

        $this->assertNotSame($context, $otherContext);
    }
}
