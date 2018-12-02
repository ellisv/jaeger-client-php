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

use OpenTracing\Reference;
use OpenTracing\Span as SpanInterface;

class Span implements SpanInterface
{
    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var string
     */
    private $operationName;

    /**
     * @var SpanContext
     */
    private $context;

    /**
     * @var int A timestamp indicating when the span began in microseconds
     */
    private $startTime;

    /**
     * @var int Duration of the span in microseconds
     */
    private $duration = 0;

    /**
     * @var array
     */
    private $tags;

    /**
     * @var LogRecord[]
     */
    private $logs = [];

    /**
     * @var Reference[]
     */
    private $references;

    public function __construct(
        Tracer $tracer,
        string $operationName,
        SpanContext $context,
        int $startTime,
        array $tags,
        array $references
    ) {
        $this->tracer = $tracer;
        $this->operationName = $operationName;
        $this->context = $context;
        $this->startTime = $startTime;
        $this->tags = $tags;
        $this->references = $references;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperationName()
    {
        return $this->operationName;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function finish($finishTime = null)
    {
        if ($finishTime === null) {
            $finishTime = $this->tracer->currentTime();
        }

        $this->duration = $finishTime - $this->startTime;
        $this->tracer->reportSpan($this);
    }

    /**
     * {@inheritdoc}
     */
    public function overwriteOperationName($newOperationName)
    {
        $this->operationName = (string) $newOperationName;
    }

    /**
     * {@inheritdoc}
     */
    public function setTag($key, $value)
    {
        $this->tags[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function log(array $fields = [], $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = $this->tracer->currentTime();
        }

        $this->logs[] = new LogRecord($timestamp, $fields);
    }

    /**
     * {@inheritdoc}
     */
    public function addBaggageItem($key, $value)
    {
        $this->context = $this->context->withBaggageItem($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getBaggageItem($key)
    {
        return $this->context->getBaggageItem($key);
    }

    public function getStartTime(): int
    {
        return $this->startTime;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getTracer(): Tracer
    {
        return $this->tracer;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return LogRecord[]
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * @return Reference[]
     */
    public function getReferences(): array
    {
        return $this->references;
    }
}
