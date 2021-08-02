<?php

declare(strict_types=1);

namespace League\Route\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

final class MiddlewareAwareTraitTest extends TestCase
{
    public function testNonResolvableLazyMiddleware(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $class = new class implements MiddlewareAwareInterface
        {
            use MiddlewareAwareTrait;

            public function resolveLazyMiddleware(string $middleware)
            {
                $this->resolveMiddleware($middleware);
            }
        };

        $class->resolveLazyMiddleware('NonResolvableMiddleware');
    }

    public function testResolvableLazyMiddlewareThroughContainer(): void
    {
        $class = new class implements MiddlewareAwareInterface
        {
            use MiddlewareAwareTrait;

            public function resolveLazyMiddleware(string $middleware, ContainerInterface $container): MiddlewareInterface
            {
                return $this->resolveMiddleware($middleware, $container);
            }
        };

        $container  = $this->createMock(ContainerInterface::class);
        $middleware = $this->createMock(MiddlewareInterface::class);

        $lazyMiddleware = 'ClassName';

        $container
            ->expects($this->once())
            ->method('get')
            ->with($lazyMiddleware)
            ->willReturn($middleware)
        ;

        $container
            ->expects($this->once())
            ->method('has')
            ->with($lazyMiddleware)
            ->willReturn(true)
        ;

        $actualMiddleware = $class->resolveLazyMiddleware($lazyMiddleware, $container);

        $this->assertSame($middleware, $actualMiddleware);
    }

    public function testResolvableLazyMiddlewareThroughDirectInstantiation(): void
    {
        $class = new class implements MiddlewareAwareInterface
        {
            use MiddlewareAwareTrait;

            public function resolveLazyMiddleware(string $middleware): MiddlewareInterface
            {
                return $this->resolveMiddleware($middleware);
            }
        };

        $middleware = $class->resolveLazyMiddleware(AnyMiddleware::class);

        $this->assertInstanceOf(AnyMiddleware::class, $middleware);
    }

    public function testShiftMiddlewareMoreShiftsThanMiddleware(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        $class = new class implements MiddlewareAwareInterface
        {
            use MiddlewareAwareTrait;
        };

        $middleware = $this->createMock(MiddlewareInterface::class);

        $numberOfMiddleware = rand(1, 4);

        for ($i = 0; $i < $numberOfMiddleware; $i++) {
            $class->middleware($middleware);
        }

        $numberOfShifts = $numberOfMiddleware + 1;

        for ($i = 0; $i < $numberOfShifts; $i++) {
            $class->shiftMiddleware();
        }
    }
}
