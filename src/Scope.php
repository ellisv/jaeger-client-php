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

use OpenTracing\Scope as ScopeInterface;
use OpenTracing\Span as SpanInterface;

class Scope implements ScopeInterface
{
    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @var SpanInterface
     */
    private $wrapped;

    /**
     * @var bool
     */
    private $finishSpanOnClose;

    /**
     * @var Scope|null
     */
    private $toRestore;

    public function __construct(ScopeManager $scopeManager, SpanInterface $wrapped, bool $finishSpanOnClose)
    {
        $this->scopeManager = $scopeManager;
        $this->wrapped = $wrapped;
        $this->finishSpanOnClose = $finishSpanOnClose;
        $this->toRestore = $scopeManager->getActive();
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->scopeManager->getActive() !== $this) {
            // This shouldn't happen if users call methods in expected order
            return;
        }

        if ($this->finishSpanOnClose) {
            $this->wrapped->finish();
        }

        $this->scopeManager->setActive($this->toRestore);
    }

    /**
     * {@inheritdoc}
     */
    public function getSpan()
    {
        return $this->wrapped;
    }
}
