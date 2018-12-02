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

class TextMapPropagatorStub extends TextMapPropagator
{
    public function inject(SpanContext $spanContext, array &$carrier): void
    {
        $carrier['uber-trace-id'] = '1:1:0:1';
        $carrier['uberctx-oil-type'] = 'olive';
        $carrier['uberctx-temperature'] = '120';
    }

    public function extract(array $carrier): ?SpanContext
    {
        return new SpanContext(new TraceId(0, 1), 1, 0, 1);
    }
}
