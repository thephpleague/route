<?php

namespace League\Route;

use League\Route\Middleware\StackAwareInterface as MiddlewareAwareInterface;
use League\Route\Middleware\StackAwareTrait as MiddlewareAwareTrait;
use League\Route\Strategy\StrategyAwareInterface;
use League\Route\Strategy\StrategyAwareTrait;

class RouteGroup implements MiddlewareAwareInterface, RouteCollectionInterface, StrategyAwareInterface
{
    use MiddlewareAwareTrait;
    use RouteCollectionMapTrait;
    use RouteConditionTrait;
    use StrategyAwareTrait;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var \League\Route\RouteCollectionInterface
     */
    protected $collection;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * Constructor.
     *
     * @param string                        $prefix
     * @param callable                      $callback
     * @param \League\Route\RouteCollection $collection
     */
    public function __construct($prefix, callable $callback, RouteCollectionInterface $collection)
    {
        $this->callback   = $callback;
        $this->collection = $collection;
        $this->prefix     = sprintf('/%s', ltrim($prefix, '/'));
    }

    /**
     * Process the group and ensure routes are added to the collection.
     *
     * @return void
     */
    public function __invoke()
    {
        call_user_func_array($this->callback, [$this]);
    }

    /**
     * {@inheritdoc}
     */
    public function map($method, $path, $handler)
    {
        $path  = ($path === '/') ? $this->prefix : $this->prefix . sprintf('/%s', ltrim($path, '/'));
        $route = $this->collection->map($method, $path, $handler);

        $route->setParentGroup($this);

        if ($host = $this->getHost()) {
            $route->setHost($host);
        }

        if ($scheme = $this->getScheme()) {
            $route->setScheme($scheme);
        }

        foreach ($this->getMiddlewareStack() as $middleware) {
            $route->middleware($middleware);
        }

        if (is_null($route->getStrategy()) && ! is_null($this->getStrategy())){
            $route->setStrategy($this->getStrategy());
        }

        return $route;
    }
}
