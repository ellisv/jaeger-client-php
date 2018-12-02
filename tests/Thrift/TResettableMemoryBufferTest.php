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

use Ellis\Jaeger\Thrift\TResettableMemoryBuffer;
use PHPUnit\Framework\TestCase;

class TResettableMemoryBufferTest extends TestCase
{
    public function testReset(): void
    {
        $buffer = new TResettableMemoryBuffer('initialBuffer');
        $this->assertGreaterThan(0, $buffer->available());

        $buffer->reset();
        $this->assertSame(0, $buffer->available());
    }
}
