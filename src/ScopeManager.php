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

use OpenTracing\ScopeManager as ScopeManagerInterface;
use OpenTracing\Span as SpanInterface;

class ScopeManager implements ScopeManagerInterface
{
    /**
     * @var Scope|null
     */
    private $active;

    /**
     * {@inheritdoc}
     */
    public function activate(SpanInterface $span, $finishSpanOnClose)
    {
        $this->active = new Scope($this, $span, $finishSpanOnClose);

        return $this->active;
    }

    /**
     * {@inheritdoc}
     */
    public function getActive()
    {
        return $this->active;
    }

    public function setActive(Scope $scope = null): void
    {
        $this->active = $scope;
    }
}
