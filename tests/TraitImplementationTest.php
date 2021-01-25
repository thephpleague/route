<?php

declare(strict_types=1);

namespace League\Route;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

class TraitImplementationTest extends TestCase
{
    public function testContainerAwareTraitSetsAndGetsContainer(): void
    {
        $class = new class implements ContainerAwareInterface
        {
            use ContainerAwareTrait;
        };

        $container = $this->createMock(ContainerInterface::class);
        $this->assertInstanceOf(ContainerAwareInterface::class, $class->setContainer($container));
        $this->assertInstanceOf(ContainerInterface::class, $class->getContainer());
    }

    public function testThrowsWhenContainerAwareTraitOnWrongInstance(): void
    {
        $this->expectException(RuntimeException::class);

        $class = new class
        {
            use ContainerAwareTrait;
        };

        $container = $this->createMock(ContainerInterface::class);
        $class->setContainer($container);
    }

    public function testThrowsWhenRouteConditionTraitOnWrongInstance(): void
    {
        $this->expectException(RuntimeException::class);

        $class = new class
        {
            use RouteConditionHandlerTrait;
        };

        $class->setHost('example.com');
    }
}
