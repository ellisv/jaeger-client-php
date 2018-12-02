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

namespace Ellis\Jaeger\Propagation;

use Ellis\Jaeger\Constants;
use Ellis\Jaeger\SpanContext;
use Ellis\Jaeger\TraceId;

class TextMapPropagator
{
    const SPAN_CONTEXT_KEY = 'uber-trace-id';
    const DEBUG_KEY = Constants::DEBUG_TAG_KEY;
    const BAGGAGE_KEY = 'jaeger-baggage';
    const BAGGAGE_KEY_PREFIX = 'uberctx-';

    public function inject(SpanContext $spanContext, array &$carrier): void
    {
        $carrier[self::SPAN_CONTEXT_KEY] = (string) $spanContext;
        foreach ($spanContext as $key => $value) {
            $carrier[self::BAGGAGE_KEY_PREFIX.$key] = $value;
        }
    }

    public function extract(array $carrier): ?SpanContext
    {
        $traceId = new TraceId(0, 0);
        $spanId = 0;
        $parentId = 0;
        $flags = 0;
        $baggage = [];
        $debugId = '';

        foreach ($carrier as $key => $value) {
            $key = strtolower($key);
            if ($key === self::SPAN_CONTEXT_KEY) {
                [$traceId, $spanId, $parentId, $flags] = $this->extractContextProperties($value);
            } elseif ($key === self::DEBUG_KEY) {
                $debugId = $value;
            } elseif ($key === self::BAGGAGE_KEY) {
                $baggage = array_merge($baggage, $this->parseCommaSeparatedBaggage($value));
            } elseif (substr($key, 0, \strlen(self::BAGGAGE_KEY_PREFIX)) === self::BAGGAGE_KEY_PREFIX) {
                $baggage[substr($key, \strlen(self::BAGGAGE_KEY_PREFIX))] = $value;
            }
        }

        if ($traceId->isValid() || !empty($baggage) || $debugId !== '') {
            return new SpanContext($traceId, $spanId, $parentId, $flags, $baggage, $debugId);
        }

        return null;
    }

    private function extractContextProperties(string $value): array
    {
        $parts = explode(':', $value);
        if (\count($parts) !== 4) {
            throw new \UnexpectedValueException('Given string does not match tracer format');
        }

        $traceId = TraceId::buildFromString($parts[0]);
        $spanId = hexdec($parts[1]);
        $parentId = hexdec($parts[2]);
        $flags = (int) $parts[3];

        return [$traceId, $spanId, $parentId, $flags];
    }

    private function parseCommaSeparatedBaggage(string $value): array
    {
        $baggage = [];
        foreach (explode(',', $value) as $pair) {
            list($key, $value) = explode('=', $pair, 2);
            $baggage[$key] = $value;
        }

        return $baggage;
    }
}
