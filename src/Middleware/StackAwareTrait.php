<?php

namespace League\Route\Middleware;

trait StackAwareTrait
{
    /**
     * @var callable[]
     */
    protected $middleware = [];

    /**
     * {@inheritdoc}
     */
    public function middleware(callable $middleware)
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddlewareStack()
    {
        return $this->middleware;
    }
}
