<?php

declare(strict_types=1);

namespace League\Route;

use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Strategy\{StrategyAwareInterface, StrategyAwareTrait};
use Override;
use Psr\Http\Server\RequestHandlerInterface;

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

    /** @var callable */
    protected $callback;

    public function __construct(
        protected string $prefix,
        callable $callback,
        protected RouteCollectionInterface $collection,
    ) {
        $this->callback = $callback;
        $this->prefix = sprintf('/%s', ltrim($this->prefix, '/'));
    }

    public function __invoke(): void
    {
        ($this->callback)($this);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param array<string>|string $method
     * @param callable|array<string>|string|RequestHandlerInterface $handler
     */
    #[Override]
    public function map(
        string|array $method,
        string $path,
        callable|array|string|RequestHandlerInterface $handler,
    ): Route {
        $path = ($path === '/') ? $this->prefix : $this->prefix . sprintf('/%s', ltrim($path, '/'));
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
