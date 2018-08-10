<?php declare(strict_types=1);

namespace League\Route\Middleware;

use Psr\Http\Server\MiddlewareInterface;

trait MiddlewareAwareTrait
{
    /**
     * @var \Psr\Http\Server\MiddlewareInterface[]
     */
    protected $middleware = [];

    /**
     * {@inheritdoc}
     */
    public function middleware(MiddlewareInterface $middleware) : MiddlewareAwareInterface
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function middlewares(array $middlewares) : MiddlewareAwareInterface
    {
        foreach ($middlewares as $middleware) {
            $this->middleware($middleware);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function prependMiddleware(MiddlewareInterface $middleware) : MiddlewareAwareInterface
    {
        array_unshift($this->middleware, $middleware);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function shiftMiddleware() : MiddlewareInterface
    {
        return array_shift($this->middleware);
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddlewareStack() : iterable
    {
        return $this->middleware;
    }
}
