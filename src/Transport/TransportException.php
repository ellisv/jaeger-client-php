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

namespace Ellis\Jaeger\Transport;

class TransportException extends \Exception
{
    private $droppedSpanCount;

    public function __construct(string $message, \Exception $previous = null, int $droppedSpanCount = 0)
    {
        parent::__construct($message, 0, $previous);

        $this->droppedSpanCount = $droppedSpanCount;
    }

    public function getDroppedSpanCount()
    {
        return $this->droppedSpanCount;
    }
}
