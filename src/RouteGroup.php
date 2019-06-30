<?php declare(strict_types=1);

namespace League\Route;

use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Strategy\{StrategyAwareInterface, StrategyAwareTrait};

class RouteGroup implements
    MiddlewareAwareInterface,
    RouteCollectionInterface,
    RouteConditionHandlerInterface,
    StrategyAwareInterface
{
    use MiddlewareAwareTrait;
    use RouteCollectionTrait;
    use RouteConditionHandlerTrait;
    use StrategyAwareTrait;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var RouteCollectionInterface
     */
    protected $collection;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * Constructor
     *
     * @param string                   $prefix
     * @param callable                 $callback
     * @param RouteCollectionInterface $collection
     */
    public function __construct(string $prefix, callable $callback, RouteCollectionInterface $collection)
    {
        $this->callback   = $callback;
        $this->collection = $collection;
        $this->prefix     = sprintf('/%s', ltrim($prefix, '/'));
    }

    /**
     * Return the prefix of the group
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Process the group and ensure routes are added to the collection
     *
     * @return void
     */
    public function __invoke(): void
    {
        ($this->callback)($this);
    }

    /**
     * {@inheritdoc}
     */
    public function map(string $method, string $path, $handler): Route
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

        if ($port = $this->getPort()) {
            $route->setPort($port);
        }

        if ($route->getStrategy() === null && $this->getStrategy() !== null) {
            $route->setStrategy($this->getStrategy());
        }

        return $route;
    }
}
