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

use Ellis\Jaeger\Reporter\RemoteReporter;
use Ellis\Jaeger\Sampler\ConstSampler;
use Ellis\Jaeger\Tracer;
use Ellis\Jaeger\Transport\UdpTransport;
use OpenTracing\Reference;
use PHPUnit\Framework\TestCase;

class TracerIntegrationTest extends TestCase
{
    const TEST_TAG = 'jaeger-test-id';

    private $tracer;
    private $queryUrl;
    private $tagValue;

    protected function setUp(): void
    {
        $agentPort = getenv('JAEGER_AGENT_PORT') !== false ? (int) getenv('JAEGER_AGENT_PORT') : 6831;
        $queryPort = getenv('JAEGER_QUERY_PORT') !== false ? (int) getenv('JAEGER_QUERY_PORT') : 16686;
        if (!$this->isSocketAvailable('udp://127.0.0.1', $agentPort)) {
            $this->markTestSkipped(sprintf('Agent unreachable on specified JAEGER_AGENT_PORT (%d)', $agentPort));
        }
        if (!$this->isSocketAvailable('127.0.0.1', $queryPort)) {
            $this->markTestSkipped(sprintf('Query unreachable on specified JAEGER_QUERY_PORT (%d)', $queryPort));
        }

        $transport = new UdpTransport('127.0.0.1', $agentPort);
        $reporter = new RemoteReporter($transport);
        $sampler = new ConstSampler(true);
        $this->tracer = new Tracer('test-service', $reporter, $sampler, null, ['host' => 'localhost']);
        $this->queryUrl = sprintf('http://127.0.0.1:%d', $queryPort);
        $this->tagValue = uniqid('test');
    }

    protected function tearDown(): void
    {
        $this->tracer->close();
    }

    public function testSingleSpan(): void
    {
        try {
            $span = $this->tracer->startSpan('HTTP GET', [
                'tags' => [
                    'http.path' => '/hello-world',
                    'http.method' => 'GET',
                    'retry' => 3,
                    self::TEST_TAG => $this->tagValue,
                ],
            ]);
            usleep(10000);
            $span->log(['event' => 'middle']);
            usleep(10000);
            $span->finish();
        } finally {
            $this->tracer->flush();
        }

        // Waiting for span to appear on API
        usleep(20000);

        $trace = $this->findTestTrace();
        $this->assertCount(1, $trace['spans']);
        $span = $trace['spans'][0];
        $this->assertSame('HTTP GET', $span['operationName']);
        $this->assertGreaterThanOrEqual(20000, $span['duration']);
        $this->assertArraySubset([
            'http.path' => '/hello-world',
            'http.method' => 'GET',
            'retry' => 3,
        ], $this->tagsToAssoc($span['tags']));

        $this->assertCount(1, $span['logs']);
        $this->assertArraySubset(['event' => 'middle'], $this->tagsToAssoc($span['logs'][0]['fields']));

        $this->assertArrayHasKey($span['processID'], $trace['processes']);
        $proc = $trace['processes'][$span['processID']];
        $this->assertSame('test-service', $proc['serviceName']);
        $this->assertArraySubset(['host' => 'localhost'], $this->tagsToAssoc($proc['tags']));
    }

    public function testReferences()
    {
        try {
            $currentTime = $this->tracer->currentTime();
            $startTime = $currentTime - 10 * 60000000;
            $makingDishSpan = $this->tracer->startSpan('makingDish', [
                'tags' => [self::TEST_TAG => $this->tagValue],
                'start_time' => $startTime,
            ]);

            $preparationSpan = $this->tracer->startSpan('preparation', [
                'child_of' => $makingDishSpan,
                'start_time' => $startTime + 60000000,
            ]);
            $cuttingSpan = $this->tracer->startSpan('cutting', [
                'child_of' => $preparationSpan,
                'start_time' => $startTime + 65000000,
            ]);
            $cuttingSpan->finish($startTime + 2 * 60000000);
            $preparationSpan->finish($startTime + 4 * 60000000);

            $frySpan = $this->tracer->startSpan('fry', [
                'child_of' => $makingDishSpan,
                'start_time' => $startTime + 5 * 60000000,
            ]);
            $frySpan->finish($startTime + 7 * 60000000);

            $makingDishSpan->finish($startTime + 8 * 60000000);

            $eatSpan = $this->tracer->startSpan('eat', [
                'references' => [Reference::create(Reference::FOLLOWS_FROM, $makingDishSpan->getContext())],
                'start_time' => $startTime + 9 * 60000000,
            ]);
            $eatSpan->finish($currentTime);
        } finally {
            $this->tracer->flush();
        }

        // Waiting for span to appear on API
        usleep(20000);

        $trace = $this->findTestTrace();
        $idByName = [];
        $referenceById = [];
        foreach ($trace['spans'] as $s) {
            $idByName[$s['operationName']] = $s['spanID'];
            if (\count($s['references']) > 0) {
                $ref = $s['references'][0];
                $referenceById[$s['spanID']] = [$ref['refType'], $ref['spanID']];
            }
        }

        $this->assertSame(['CHILD_OF', $idByName['makingDish']], $referenceById[$idByName['preparation']]);
        $this->assertSame(['CHILD_OF', $idByName['preparation']], $referenceById[$idByName['cutting']]);
        $this->assertSame(['CHILD_OF', $idByName['makingDish']], $referenceById[$idByName['fry']]);
        $this->assertSame(['FOLLOWS_FROM', $idByName['makingDish']], $referenceById[$idByName['eat']]);
    }

    public function testManySpans()
    {
        try {
            $currentTime = $this->tracer->currentTime();
            $startTime = $currentTime - 10000 * 2000;
            $root = $this->tracer->startSpan('root', [
                'start_time' => $startTime - 2000,
                'tags' => [self::TEST_TAG => $this->tagValue],
            ]);
            for ($i = 0; $i < 10000; ++$i) {
                $spanStartTime = $startTime + $i * 2000;
                $span = $this->tracer->startSpan('child', [
                    'child_of' => $root,
                    'start_time' => $spanStartTime,
                ]);
                $span->finish($spanStartTime + 1900);
            }
            $root->finish($currentTime);
        } finally {
            $this->tracer->flush();
        }

        // Waiting for span to appear on API
        usleep(20000);

        $trace = $this->findTestTrace();
        $this->assertCount(10001, $trace['spans']);
    }

    private function isSocketAvailable(string $host, int $port): bool
    {
        try {
            $fp = fsockopen($host, $port, $errno, $erstr, 3);
            if (!$fp) {
                return false;
            }
            fclose($fp);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function findTestTrace(): array
    {
        $res = $this->getFromApi('/api/traces?'.http_build_query([
            'service' => 'test-service',
            'limit' => 10,
            'lookback' => '1h',
            'tags' => json_encode([self::TEST_TAG => $this->tagValue]),
        ]));

        $this->assertCount(1, $res['data']);

        return $res['data'][0];
    }

    private function getFromApi(string $path): array
    {
        $url = sprintf('%s/%s', $this->queryUrl, $path);
        $res = file_get_contents($url);
        if ($res === false) {
            $this->fail(sprintf('Can not retrieve a response from %s', $url));

            return [];
        }

        $json = json_decode($res, true);
        if ($res === null) {
            $this->fail(sprintf('Can not decode a response of "%s" to JSON', $res));

            return [];
        }

        return $json;
    }

    private function tagsToAssoc(array $tags): array
    {
        $res = [];
        foreach ($tags as $tag) {
            $res[$tag['key']] = $tag['value'];
        }

        return $res;
    }
}
