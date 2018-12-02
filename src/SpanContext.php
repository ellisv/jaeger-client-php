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

namespace Ellis\Jaeger;

use OpenTracing\SpanContext as SpanContextInterface;

class SpanContext implements SpanContextInterface
{
    const FLAG_SAMPLED = 1;
    const FLAG_DEBUG = 2;

    private $traceId;
    private $spanId;
    private $parentId;
    private $flags;
    private $baggage;
    private $debugId;

    public function __construct(
        TraceId $traceId,
        int $spanId,
        int $parentId,
        int $flags,
        array $baggage = [],
        string $debugId = ''
    ) {
        $this->traceId = $traceId;
        $this->spanId = $spanId;
        $this->parentId = $parentId;
        $this->flags = $flags;
        $this->baggage = $baggage;
        $this->debugId = $debugId;
    }

    public function __toString(): string
    {
        return sprintf('%s:%x:%x:%x', ((string) $this->traceId), $this->spanId, $this->parentId, $this->flags);
    }

    /**
     * {@inheritdoc}
     */
    public function getBaggageItem($key)
    {
        return $this->baggage[$key] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function withBaggageItem($key, $value)
    {
        return new static(
            $this->traceId,
            $this->spanId,
            $this->parentId,
            $this->flags,
            array_merge($this->baggage, [$key => $value]),
            $this->debugId
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->baggage);
    }

    public function getBaggage(): array
    {
        return $this->baggage;
    }

    public function getTraceId(): TraceId
    {
        return $this->traceId;
    }

    public function getSpanId(): int
    {
        return $this->spanId;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function getDebugId(): string
    {
        return $this->debugId;
    }

    public function isSampled(): bool
    {
        return ($this->flags & self::FLAG_SAMPLED) === self::FLAG_SAMPLED;
    }

    public function isDebug(): bool
    {
        return ($this->flags & self::FLAG_DEBUG) === self::FLAG_DEBUG;
    }

    public function isValid(): bool
    {
        return $this->traceId->isValid() && $this->spanId !== 0;
    }
}
