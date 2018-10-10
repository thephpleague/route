<?php declare(strict_types=1);

namespace League\Route\Middleware;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareAwareInterface
{
    /**
     * Add a middleware to the stack
     *
     * @param \Psr\Http\Server\MiddlewareInterface $middleware
     *
     * @return static
     */
    public function middleware(MiddlewareInterface $middleware) : MiddlewareAwareInterface;

    /**
     * Add multiple middleware to the stack
     *
     * @param \Psr\Http\Server\MiddlewareInterface[] $middlewares
     *
     * @return static
     */
    public function middlewares(array $middlewares) : MiddlewareAwareInterface;

    /**
     * Prepend a middleware to the stack
     *
     * @param \Psr\Http\Server\MiddlewareInterface $middleware
     *
     * @return static
     */
    public function prependMiddleware(MiddlewareInterface $middleware) : MiddlewareAwareInterface;

    /**
     * Shift a middleware from beginning of stack
     *
     * @return \Psr\Http\Server\MiddlewareInterface|null
     */
    public function shiftMiddleware() : MiddlewareInterface;

    /**
     * Get the stack of middleware
     *
     * @return \Psr\Http\Server\MiddlewareInterface[]
     */
    public function getMiddlewareStack() : iterable;
}
