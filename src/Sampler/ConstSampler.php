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

use Ellis\Jaeger\Constants;
use Ellis\Jaeger\TraceId;

class ConstSampler implements SamplerInterface
{
    private $samplingStatus;

    public function __construct(bool $sample)
    {
        $this->samplingStatus = new SamplingStatus($sample, [
            Constants::SAMPLER_TYPE_TAG_KEY => 'const',
            Constants::SAMPLER_PARAM_TAG_KEY => $sample,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function isSampled(TraceId $traceId, string $operationName): SamplingStatus
    {
        return $this->samplingStatus;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        // no-op
    }
}
