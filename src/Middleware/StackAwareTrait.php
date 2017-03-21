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
        // because the execution chain works front to back, later middlewares
        // need to be prepended to the stack in order to run in order
        array_unshift($this->middleware, $middleware);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function middlewares()
    {
        $middlewares = func_get_args();

        foreach ($middlewares as $middleware) {
            if (is_array($middleware)) {
                call_user_func_array(array($this, 'middlewares'), $middleware);
            } else {
                $this->middleware($middleware);
            }
        }

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
