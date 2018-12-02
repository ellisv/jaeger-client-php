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

class TraceId
{
    private $high;
    private $low;

    public function __construct(int $high, int $low)
    {
        $this->high = $high;
        $this->low = $low;
    }

    public function __toString(): string
    {
        if (!$this->high) {
            return sprintf('%x', $this->low);
        }

        return sprintf('%x%016x', $this->high, $this->low);
    }

    public function getHigh(): int
    {
        return $this->high;
    }

    public function getLow(): int
    {
        return $this->low;
    }

    public function isValid(): bool
    {
        return $this->high !== 0 || $this->low !== 0;
    }

    public static function buildFromString(string $str): self
    {
        if (\strlen($str) > 32) {
            throw new \UnexpectedValueException('Tried to build TraceId with string value longer than 32');
        }
        if (\strlen($str) > 16) {
            $high = hexdec(substr($str, 0, \strlen($str) - 16));
            $low = hexdec(substr($str, -16));
        } else {
            $high = 0;
            $low = hexdec($str);
        }

        return new self($high, $low);
    }
}
