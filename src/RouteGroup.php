<?php

namespace League\Route;

use Closure;
use League\Route\RouteCollection;

class RouteGroup implements RouteCollectionInterface
{
    use RouteCollectionMapTrait;
    use RouteConditionTrait;

    /**
     * @var \League\Route\RouteCollection
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
     * @param \Closure                      $callback
     * @param \League\Route\RouteCollection $collection
     */
    public function __construct($prefix, Closure $callback, RouteCollection $collection)
    {
        $this->prefix     = str_pad($prefix, 1, '/', STR_PAD_LEFT);
        $this->collection = $collection;

        $callback->bindTo($this);
    }

    /**
     * {@inheritdoc}
     */
    public function map($method, $path, callable $handler)
    {
        $path = $this->prefix . str_pad($prefix, 1, '/', STR_PAD_LEFT);
        return $this->collection->map($method, $path, $handler);
    }
}
