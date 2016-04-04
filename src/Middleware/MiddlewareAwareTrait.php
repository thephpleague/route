<?php

namespace League\Route\Middleware;

use RuntimeException;

trait MiddlewareAwareTrait
{
    /**
     * @var \League\Route\Middleware\Runner
     */
    protected $middlewareRunner;

    /**
     * @var callable[]
     */
    protected $middlewareBefore = [];

    /**
     * @var callable[]
     */
    protected $middlewareAfter = [];

    /**
     * {@inheritdoc}
     */
    public function setMiddlewareRunner(Runner $runner)
    {
        $this->middlewareRunner = $runner;
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddlewareRunner()
    {
        if (is_null($this->middlewareRunner)) {
            $this->middlewareRunner = new Runner;
        }

        return $this->middlewareRunner;
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddlewareBeforeQueue()
    {
        return $this->middlewareBefore;
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddlewareAfterQueue()
    {
        return $this->middlewareAfter;
    }

    /**
     * {@inheritdoc}
     */
    public function before(callable $middleware)
    {
        $this->middlewareBefore[] = $middleware;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function after(callable $middleware)
    {
        $this->middlewareAfter[] = $middleware;

        return $this;
    }
}
