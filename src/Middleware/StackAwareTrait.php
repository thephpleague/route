<?php

namespace League\Route\Middleware;

use Psr\Http\Server\MiddlewareInterface;

trait StackAwareTrait
{
    /**
     * @var callable[]
     */
    protected $middleware = [];

    /**
     * {@inheritdoc}
     */
    public function middleware($middleware)
    {
        if (!is_callable($middleware) && !$middleware instanceof MiddlewareInterface) {
            throw new InvalidMiddlewareException();
        }
        $this->middleware[] = $middleware;

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
