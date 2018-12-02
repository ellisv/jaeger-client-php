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

namespace Ellis\Jaeger\Propagation;

use Ellis\Jaeger\SpanContext;
use Psr\Http\Message\MessageInterface;

class HttpHeadersPropagator
{
    private $textMapPropagator;

    public function __construct(TextMapPropagator $textMapPropagator)
    {
        $this->textMapPropagator = $textMapPropagator;
    }

    public function inject(SpanContext $spanContext, MessageInterface &$carrier): void
    {
        $textMapCarrier = [];
        $this->textMapPropagator->inject($spanContext, $textMapCarrier);
        foreach ($textMapCarrier as $name => $value) {
            $carrier = $carrier->withHeader($name, $value);
        }
    }

    public function extract(MessageInterface $carrier): ?SpanContext
    {
        $textMapCarrier = [];
        foreach ($carrier->getHeaders() as $name => $values) {
            $len = \count($values);
            if ($len > 0) {
                $textMapCarrier[$name] = $values[$len - 1];
            }
        }

        return $this->textMapPropagator->extract($textMapCarrier);
    }
}
