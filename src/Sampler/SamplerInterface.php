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

namespace Ellis\Jaeger\Sampler;

use Ellis\Jaeger\TraceId;

interface SamplerInterface
{
    public function isSampled(TraceId $traceId, string $operationName): SamplingStatus;

    public function close(): void;
}
