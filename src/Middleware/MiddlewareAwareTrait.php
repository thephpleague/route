<?php declare(strict_types=1);

namespace League\Route\Middleware;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

trait MiddlewareAwareTrait
{
    /**
     * @var array
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
     * Add a middleware as a class name to the stack
     *
     * @param string $middleware
     *
     * @return static
     */
    public function lazyMiddleware(string $middleware) : MiddlewareAwareInterface
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * Add multiple middlewares as class names to the stack
     *
     * @param string[] $middlewares
     *
     * @return static
     */
    public function lazyMiddlewares(array $middlewares) : MiddlewareAwareInterface
    {
        foreach ($middlewares as $middleware) {
            $this->lazyMiddleware($middleware);
        }

        return $this;
    }

    /**
     * Prepend a middleware as a class name to the stack
     *
     * @param string $middleware
     *
     * @return static
     */
    public function lazyPrependMiddleware(string $middleware) : MiddlewareAwareInterface
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

    /**
     * Resolve a middleware implementation, optionally from a container
     *
     * @param \Psr\Http\Server\MiddlewareInterface|string $middleware
     * @param \Psr\Container\ContainerInterface|null      $container
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    protected function resolveMiddleware($middleware, ?ContainerInterface $container = null) : MiddlewareInterface
    {
        if (is_null($container) && is_string($middleware) && class_exists($middleware)) {
            $middleware = new $middleware;
        }

        if (! is_null($container) && is_string($middleware) && $container->has($middleware)) {
            $middleware = $container->get($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        throw new InvalidArgumentException(sprintf('Could not resolve middleware class: %s', $middleware));
    }
}
