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

class LogRecord
{
    private $timestamp;
    private $fields;

    public function __construct(int $timestamp, array $fields)
    {
        $this->timestamp = $timestamp;
        $this->fields = $fields;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getFields(): array
    {
        return $this->fields;
    }
}
