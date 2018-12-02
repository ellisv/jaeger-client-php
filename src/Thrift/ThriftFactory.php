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

namespace Ellis\Jaeger\Thrift;

use Ellis\Jaeger\LogRecord;
use Ellis\Jaeger\Span;
use Jaeger\Thrift\Log;
use Jaeger\Thrift\Process as ThriftProcess;
use Jaeger\Thrift\Span as ThriftSpan;
use Jaeger\Thrift\SpanRef;
use Jaeger\Thrift\SpanRefType;
use Jaeger\Thrift\Tag;
use Jaeger\Thrift\TagType;
use OpenTracing\Reference;

class ThriftFactory
{
    public static function buildSpan(Span $span): ThriftSpan
    {
        $context = $span->getContext();
        $traceId = $context->getTraceId();

        return new ThriftSpan([
            'traceIdLow' => $traceId->getLow(),
            'traceIdHigh' => $traceId->getHigh(),
            'spanId' => $context->getSpanId(),
            'parentSpanId' => $context->getParentId(),
            'operationName' => $span->getOperationName(),
            'references' => self::buildReferences($span->getReferences()),
            'flags' => $context->getFlags(),
            'startTime' => $span->getStartTime(),
            'duration' => $span->getDuration(),
            'tags' => self::buildTags($span->getTags()),
            'logs' => self::buildLogs($span->getLogs()),
        ]);
    }

    public static function buildProcess(Span $span): ThriftProcess
    {
        $process = $span->getTracer()->getProcess();

        return new ThriftProcess([
            'serviceName' => $process->getServiceName(),
            'tags' => self::buildTags($process->getTags()),
        ]);
    }

    /**
     * @param array $tags
     *
     * @return Tag[]
     */
    private static function buildTags(array $tags): array
    {
        $tTags = [];
        foreach ($tags as $key => $value) {
            $tTags[] = self::buildTag($key, $value);
        }

        return $tTags;
    }

    /**
     * @param LogRecord[] $logs
     *
     * @return Log[]
     */
    private static function buildLogs(array $logs): array
    {
        return array_map(function (LogRecord $record): Log {
            $fields = [];
            foreach ($record->getFields() as $key => $value) {
                $fields[] = self::buildTag($key, $value);
            }

            return new Log(['timestamp' => $record->getTimestamp(), 'fields' => $fields]);
        }, $logs);
    }

    private static function buildTag(string $key, $value): Tag
    {
        // TODO: Implement max tag value length
        switch (\gettype($value)) {
            case 'string':
                return new Tag(['key' => $key, 'vType' => TagType::STRING, 'vStr' => trim($value)]);
            case 'double':
                return new Tag(['key' => $key, 'vType' => TagType::DOUBLE, 'vDouble' => $value]);
            case 'boolean':
                return new Tag(['key' => $key, 'vType' => TagType::BOOL, 'vBool' => $value]);
            case 'integer':
                return new Tag(['key' => $key, 'vType' => TagType::LONG, 'vLong' => $value]);
            default:
                return new Tag(['key' => $key, 'vType' => TagType::STRING, 'vStr' => ((string) trim($value))]);
        }
    }

    /**
     * @param Reference[] $references
     *
     * @return SpanRef[]
     */
    private static function buildReferences(array $references): array
    {
        $tRefs = [];
        foreach ($references as $ref) {
            if ($ref->isType(Reference::CHILD_OF)) {
                $refType = SpanRefType::CHILD_OF;
            } elseif ($ref->isType(Reference::FOLLOWS_FROM)) {
                $refType = SpanRefType::FOLLOWS_FROM;
            } else {
                continue;
            }

            $ctx = $ref->getContext();
            $traceId = $ctx->getTraceId();
            $tRefs[] = new SpanRef([
                'refType' => $refType,
                'traceIdLow' => $traceId->getLow(),
                'traceIdHigh' => $traceId->getHigh(),
                'spanId' => $ctx->getSpanId(),
            ]);
        }

        return $tRefs;
    }
}
