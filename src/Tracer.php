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

use Ellis\Jaeger\Propagation\HttpHeadersPropagator;
use Ellis\Jaeger\Propagation\TextMapPropagator;
use Ellis\Jaeger\Reporter\RemoteReporter;
use Ellis\Jaeger\Reporter\ReporterInterface;
use Ellis\Jaeger\Sampler\SamplerInterface;
use OpenTracing\Exceptions\UnsupportedFormat;
use OpenTracing\Formats;
use OpenTracing\Reference;
use OpenTracing\SpanContext as SpanContextInterface;
use OpenTracing\StartSpanOptions;
use OpenTracing\Tracer as TracerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Tracer implements TracerInterface
{
    private $reporter;
    private $sampler;
    private $scopeManager;
    private $logger;
    private $process;
    private $propagators;

    public function __construct(
        string $serviceName,
        ReporterInterface $reporter,
        SamplerInterface $sampler,
        ?LoggerInterface $logger = null,
        array $tags = []
    ) {
        $this->reporter = $reporter;
        $this->sampler = $sampler;
        $this->scopeManager = new ScopeManager();
        $this->logger = $logger ?: new NullLogger();
        $this->process = new Process($serviceName, $tags);

        $textMapPropagator = new TextMapPropagator();
        $this->propagators = [
            Formats\TEXT_MAP => $textMapPropagator,
            Formats\HTTP_HEADERS => new HttpHeadersPropagator($textMapPropagator),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeManager()
    {
        return $this->scopeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveSpan()
    {
        $activeScope = $this->scopeManager->getActive();
        if ($activeScope === null) {
            return null;
        }

        return $activeScope->getSpan();
    }

    /**
     * {@inheritdoc}
     */
    public function startActiveSpan($operationName, $options = [])
    {
        if (!$options instanceof StartSpanOptions) {
            $options = StartSpanOptions::create($options);
        }

        $span = $this->startSpan($operationName, $options);

        return $this->scopeManager->activate($span, $options->shouldFinishSpanOnClose());
    }

    public function startSpan($operationName, $options = [])
    {
        // TODO: Zipkin-style RPC spans.
        // TODO: Configurable 128bit support
        if (!$options instanceof StartSpanOptions) {
            $options = StartSpanOptions::create($options);
        }

        if (!$this->hasParentInOptions($options) && $this->getActiveSpan() !== null) {
            $parent = $this->getActiveSpan()->getContext();
            $options = $options->withParent($parent);
        }

        $references = [];
        $parentCtx = null;
        $hasParent = false;
        foreach ($options->getReferences() as $ref) {
            $ctx = $ref->getContext();
            if (!$ctx instanceof SpanContext) {
                $this->logger->error(sprintf(
                    'Reference contains invalid type of SpanContext. Got %s but %s is expected',
                    \get_class($ctx),
                    SpanContext::class
                ));

                continue;
            }
            if (!$ctx->isValid() && !$ctx->getDebugId() && \count($ctx->getBaggage()) === 0) {
                continue;
            }
            $references[] = $ref;
            if (!$hasParent) {
                $parentCtx = $ctx;
                $hasParent = $ref->isType(Reference::CHILD_OF);
            }
        }

        $samplerTags = [];
        if ($parentCtx === null || !$parentCtx->isValid()) {
            $traceId = new TraceId($this->randomId(), $this->randomId());
            $spanId = $traceId->getLow();
            $parentId = 0;
            $flags = 0;

            if ($parentCtx !== null && $parentCtx->getDebugId()) {
                $flags |= SpanContext::FLAG_SAMPLED | SpanContext::FLAG_DEBUG;
                $samplerTags = [Constants::DEBUG_TAG_KEY => $parentCtx->getDebugId()];
            } else {
                $samplingStatus = $this->sampler->isSampled($traceId, $operationName);
                if ($samplingStatus->isSampled()) {
                    $flags |= SpanContext::FLAG_SAMPLED;
                    $samplerTags = $samplingStatus->getTags();
                }
            }
        } else {
            $traceId = $parentCtx->getTraceId();
            $spanId = $this->randomId();
            $parentId = $parentCtx->getSpanId();
            $flags = $parentCtx->getFlags();
        }
        $baggage = $parentCtx === null ? [] : $parentCtx->getBaggage();

        $ctx = new SpanContext($traceId, $spanId, $parentId, $flags, $baggage);

        return new Span(
            $this,
            $operationName,
            $ctx,
            $this->resolveStartTime($options),
            array_merge($options->getTags(), $samplerTags),
            $references
        );
    }

    /**
     * {@inheritdoc}
     */
    public function inject(SpanContextInterface $spanContext, $format, &$carrier)
    {
        if (!$spanContext instanceof SpanContext) {
            throw new \InvalidArgumentException(
                sprintf('Expected %s but got %s instead', SpanContext::class, \get_class($spanContext))
            );
        }
        if (!isset($this->propagators[$format])) {
            throw UnsupportedFormat::forFormat($format);
        }
        $this->propagators[$format]->inject($spanContext, $carrier);
    }

    /**
     * {@inheritdoc}
     */
    public function extract($format, $carrier)
    {
        if (!isset($this->propagators[$format])) {
            throw UnsupportedFormat::forFormat($format);
        }

        return $this->propagators[$format]->extract($carrier);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if ($this->reporter instanceof RemoteReporter) {
            $this->reporter->flush();
        }
    }

    public function close(): void
    {
        $this->reporter->close();
        $this->sampler->close();
    }

    public function reportSpan(Span $span): void
    {
        if ($span->getContext()->isSampled()) {
            $this->reporter->report($span);
        }
    }

    public function currentTime(): int
    {
        $time = gettimeofday();

        return (int) sprintf('%d%06d', $time['sec'], $time['usec']);
    }

    public function getProcess(): Process
    {
        return $this->process;
    }

    private function hasParentInOptions(StartSpanOptions $options)
    {
        $references = $options->getReferences();
        foreach ($references as $ref) {
            if ($ref->isType(Reference::CHILD_OF)) {
                return $ref->getContext();
            }
        }

        return null;
    }

    private function resolveStartTime(StartSpanOptions $options): int
    {
        $startTime = $options->getStartTime();

        if ($startTime !== null) {
            return (int) $startTime;
        }

        return $this->currentTime();
    }

    private function randomId(): int
    {
        return rand() << 32 | rand();
    }
}
