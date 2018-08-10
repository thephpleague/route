<?php declare(strict_types=1);

namespace League\Route\Middleware;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareAwareInterface
{
    /**
     * Add a middleware to the stack.
     *
     * @param \Psr\Http\Server\MiddlewareInterface $middleware
     *
     * @return self
     */
    public function middleware(MiddlewareInterface $middleware) : MiddlewareAwareInterface;

    /**
     * Add a middleware to the stack.
     *
     * @param \Psr\Http\Server\MiddlewareInterface[] $middlewares
     *
     * @return self
     */
    public function middlewares(array $middlewares) : MiddlewareAwareInterface;

    /**
     * Add a middleware to the stack.
     *
     * @param \Psr\Http\Server\MiddlewareInterface $middleware
     *
     * @return self
     */
    public function prependMiddleware(MiddlewareInterface $middleware) : MiddlewareAwareInterface;

    /**
     * Shift middleware from beginning of stack.
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    public function shiftMiddleware() : MiddlewareInterface;

    /**
     * Get the stack of middleware
     *
     * @return \Psr\Http\Server\MiddlewareInterface[]
     */
    public function getMiddlewareStack() : iterable;
}
