<?php

namespace League\Route\Middleware;

interface StackAwareInterface
{
    /**
     * Add a middleware to the stack.
     *
     * @param callable $middleware
     *
     * @return self
     */
    public function middleware(callable $middleware);

    /**
     * Get the middleware stack.
     *
     * @return callable[]
     */
    public function getMiddlewareStack();
}
