<?php declare(strict_types=1);

namespace League\Route\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareAwareTraitTest extends TestCase
{
    public function testShiftMiddleware()
    {
        $middlewareAwareClass = new class implements MiddlewareAwareInterface
        {
            use MiddlewareAwareTrait;
        };

        $middlewareA = new class implements MiddlewareInterface
        {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
            }
        };

        $middlewareB = clone $middlewareA;

        $middlewareAwareClass->middlewares([$middlewareA, $middlewareB]);

        TestCase::assertEquals($middlewareA, $middlewareAwareClass->shiftMiddleware());
        TestCase::assertEquals($middlewareB, $middlewareAwareClass->shiftMiddleware());
        TestCase::assertNull($middlewareAwareClass->shiftMiddleware());
    }
}
