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

use Ellis\Jaeger\Span;

interface TransportInterface
{
    /**
     * @throws TransportException
     *
     * @return int Number of spans that has been flushed
     */
    public function append(Span $span): int;

    /**
     * @throws TransportException
     *
     * @return int Number of spans that has been flushed
     */
    public function flush(): int;

    public function close(): void;
}
