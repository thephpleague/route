<?php

namespace League\Route\Middleware;

interface MiddlewareAwareInterface
{
    /**
     * Set the middleware runner.
     *
     * @param \League\Route\Middleware\Runner $runner
     *
     * @return self
     */
    public function setMiddlewareRunner(Runner $runner);

    /**
     * Get the middleware runner.
     *
     * @return \League\Route\Middleware\Runner
     */
    public function getMiddlewareRunner();

    /**
     * Return the queue of before middleware.
     *
     * @return callable[]
     */
    public function getMiddlewareBeforeQueue();

    /**
     * Return the queue of after middleware.
     *
     * @return callable[]
     */
    public function getMiddlewareAfterQueue();

    /**
     * Queue a middleware to run before the route callable.
     *
     * @param callable $middleware
     *
     * @return self
     */
    public function before(callable $middleware);

    /**
     * Queue a middleware to run after the route callable.
     *
     * @param callable $middleware
     *
     * @return self
     */
    public function after(callable $middleware);
}
