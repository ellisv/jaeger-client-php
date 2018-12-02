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

use Thrift\Transport\TMemoryBuffer;

class TResettableMemoryBuffer extends TMemoryBuffer
{
    public function reset()
    {
        $this->buf_ = '';
    }
}
