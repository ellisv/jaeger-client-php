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

namespace Ellis\Jaeger\Reporter;

use Ellis\Jaeger\Span;

class NullReporter implements ReporterInterface
{
    public function report(Span $span): void
    {
        // no-op
    }

    public function close(): void
    {
        // no-op
    }
}
