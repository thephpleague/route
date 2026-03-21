<?php

declare(strict_types=1);

namespace League\Route\Middleware;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareAwareInterface
{
    /** @return iterable<MiddlewareInterface|string> */
    public function getMiddlewareStack(): iterable;
    public function lazyMiddleware(string $middleware): MiddlewareAwareInterface;

    /** @param array<string> $middlewares */
    public function lazyMiddlewares(array $middlewares): MiddlewareAwareInterface;
    public function lazyPrependMiddleware(string $middleware): MiddlewareAwareInterface;
    public function middleware(MiddlewareInterface $middleware): MiddlewareAwareInterface;

    /** @param array<MiddlewareInterface> $middlewares */
    public function middlewares(array $middlewares): MiddlewareAwareInterface;
    public function prependMiddleware(MiddlewareInterface $middleware): MiddlewareAwareInterface;
    public function shiftMiddleware(): MiddlewareInterface;
}
