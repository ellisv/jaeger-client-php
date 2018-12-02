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

use Thrift\Factory\TProtocolFactory;

class ThriftSizeCalculator
{
    private $buffer;
    private $protocol;

    public function __construct(TProtocolFactory $protocolFactory)
    {
        $this->buffer = new TResettableMemoryBuffer();
        $this->protocol = $protocolFactory->getProtocol($this->buffer);
    }

    public function size($thriftStruct): int
    {
        $this->buffer->reset();
        $thriftStruct->write($this->protocol);

        return $this->buffer->available();
    }
}
