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

use Ellis\Jaeger\TraceId;
use PHPUnit\Framework\TestCase;

class TraceIdTest extends TestCase
{
    /**
     * @dataProvider dataProvideToString
     */
    public function testToString(int $high, int $low, string $expected): void
    {
        $traceId = new TraceId($high, $low);

        $this->assertSame($expected, (string) $traceId);
    }

    public function dataProvideToString(): array
    {
        return [
            'all zeros' => [0, 0, '0'],
            'without trace id high' => [0, 453, '1c5'],
            'with trace id high' => [452, 453, '1c400000000000001c5'],
        ];
    }

    /**
     * @dataProvider dataProvideBuildFromString
     */
    public function testBuildFromString(string $str, int $expectedHigh, int $expectedLow): void
    {
        $traceId = TraceId::buildFromString($str);
        $this->assertSame($expectedHigh, $traceId->getHigh());
        $this->assertSame($expectedLow, $traceId->getLow());
    }

    public function dataProvideBuildFromString(): array
    {
        return [
            'all zeros' => ['0', 0, 0],
            'with low only' => ['4b0f6813394d311f', 0, 5408656109270282527],
            'with high and low' => ['4a10066b6119ca445521450862ee3620', 5336772616694385220, 6134260069777159712],
        ];
    }
}
