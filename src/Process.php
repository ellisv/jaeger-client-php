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

namespace Ellis\Jaeger;

class Process
{
    private $serviceName;
    private $tags;

    public function __construct(string $serviceName, array $tags)
    {
        $this->serviceName = $serviceName;
        $this->tags = $tags;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getTags(): array
    {
        return $this->tags;
    }
}
