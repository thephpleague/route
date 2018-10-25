<?php declare(strict_types=1);

namespace League\Route;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerAwareTraitTest extends TestCase
{
    public function testMainFunctionality()
    {
        $classWithTrait = new class implements ContainerAwareInterface
        {
            use ContainerAwareTrait;
        };

        TestCase::assertNull($classWithTrait->getContainer());

        $container = $this->prophesize(ContainerInterface::class)->reveal();

        TestCase::assertSame($classWithTrait, $classWithTrait->setContainer($container));
        TestCase::assertEquals($container, $classWithTrait->getContainer());
    }
}
