<?php

namespace League\Route;

class RouteGroup implements RouteCollectionInterface
{
    use RouteCollectionMapTrait;
    use RouteConditionTrait;

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

        return $route;
    }
}
