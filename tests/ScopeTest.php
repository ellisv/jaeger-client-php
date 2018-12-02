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

use Ellis\Jaeger\Scope;
use Ellis\Jaeger\ScopeManager;
use OpenTracing\Span as SpanInterface;
use PHPUnit\Framework\TestCase;

class ScopeTest extends TestCase
{
    private $span;
    private $manager;
    private $scopeToRestore;

    protected function setUp(): void
    {
        $this->span = $this->createMock(SpanInterface::class);
        $this->manager = $this->createMock(ScopeManager::class);
        $this->scopeToRestore = $this->createMock(Scope::class);

        $this->manager->expects($this->at(0))
            ->method('getActive')
            ->willReturn($this->scopeToRestore)
        ;
    }

    public function testRestoresToActiveScope(): void
    {
        $scope = $this->buildScope(false);

        $this->manager->expects($this->once())
            ->method('setActive')
            ->with($this->equalTo($this->scopeToRestore))
        ;

        $scope->close();
    }

    public function testFinishesSpanOnCloseWhenOptionIsTrue(): void
    {
        $this->span->expects($this->once())
            ->method('finish')
        ;

        $scope = $this->buildScope(true);

        $scope->close();
    }

    public function testDoesNotFinishSpanOnCloseWhenOptionIsFalse(): void
    {
        $this->span->expects($this->never())
            ->method('finish')
        ;

        $scope = $this->buildScope(false);

        $scope->close();
    }

    private function buildScope(bool $finishSpanOnClose): Scope
    {
        $scope = new Scope($this->manager, $this->span, $finishSpanOnClose);
        $this->manager->method('getActive')
            ->willReturn($scope)
        ;

        return $scope;
    }
}
