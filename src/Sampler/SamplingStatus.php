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

class SamplingStatus
{
    private $isSampled;
    private $tags;

    public function __construct(bool $isSampled, array $tags)
    {
        $this->isSampled = $isSampled;
        $this->tags = $tags;
    }

    public function isSampled(): bool
    {
        return $this->isSampled;
    }

    public function getTags(): array
    {
        return $this->tags;
    }
}
