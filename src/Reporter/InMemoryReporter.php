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

class InMemoryReporter implements ReporterInterface
{
    private $spans = [];

    public function report(Span $span): void
    {
        $this->spans[] = $span;
    }

    public function close(): void
    {
        // no-op
    }

    public function getSpans(): array
    {
        return $this->spans;
    }

    public function reset(): void
    {
        $this->spans = [];
    }
}
