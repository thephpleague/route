<?php

namespace League\Route;

use League\Route\RouteCollection;

class RouteGroup implements RouteCollectionInterface
{
    use RouteCollectionMapTrait;
    use RouteConditionTrait;

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
        $this->prefix     = str_pad($prefix, 1, '/', STR_PAD_LEFT);
        $this->collection = $collection;

        $callback($this);
    }

    /**
     * {@inheritdoc}
     */
    public function map($method, $path, callable $handler)
    {
        $path  = $this->prefix . str_pad($path, 1, '/', STR_PAD_LEFT);
        $route = $this->collection->map($method, $path, $handler);

        if ($host = $this->getHost()) {
            $route->setHost($host);
        }

        if ($scheme = $this->getScheme()) {
            $route->setScheme($scheme);
        }

        return $route;
    }
}
