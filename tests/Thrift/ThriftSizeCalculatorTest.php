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

namespace Ellis\Jaeger\Tests\Thrift;

use Ellis\Jaeger\Thrift\ThriftSizeCalculator;
use Jaeger\Thrift\Process;
use PHPUnit\Framework\TestCase;
use Thrift\Factory\TBinaryProtocolFactory;
use Thrift\Factory\TCompactProtocolFactory;
use Thrift\Factory\TProtocolFactory;

class ThriftSizeCalculatorTest extends TestCase
{
    /**
     * @dataProvider dataProvideSize
     *
     * @param mixed $thriftStruct
     */
    public function testSize(TProtocolFactory $protocolFactory, $thriftStruct, int $expectedSize): void
    {
        $calculator = new ThriftSizeCalculator($protocolFactory);
        $actualSize = $calculator->size($thriftStruct);

        $this->assertSame($expectedSize, $actualSize);
    }

    public function dataProvideSize(): array
    {
        $compact = new TCompactProtocolFactory();
        $binary = new TBinaryProtocolFactory();

        return [
            'compact process with service name only' => [
                $compact,
                new Process(['serviceName' => 'svcName']),
                10,
            ],
            'binary process with service name only' => [
                $binary,
                new Process(['serviceName' => 'svcName']),
                15,
            ],
        ];
    }
}
