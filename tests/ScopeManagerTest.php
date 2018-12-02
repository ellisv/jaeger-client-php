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

namespace Ellis\Jaeger\Tests;

use Ellis\Jaeger\ScopeManager;
use Ellis\Jaeger\Span;
use PHPUnit\Framework\TestCase;

class ScopeManagerTest extends TestCase
{
    /**
     * @var ScopeManager
     */
    private $manager;

    protected function setUp()
    {
        $this->manager = new ScopeManager();
    }

    public function testAbleToGetActiveScope(): void
    {
        $this->assertNull($this->manager->getActive());

        $span = $this->createMock(Span::class);
        $scope = $this->manager->activate($span, false);

        $this->assertSame($scope, $this->manager->getActive());
    }

    public function testScopeCloseDeactivates(): void
    {
        $span = $this->createMock(Span::class);
        $scope = $this->manager->activate($span, false);

        $scope->close();

        $this->assertNull($this->manager->getActive());
    }
}
