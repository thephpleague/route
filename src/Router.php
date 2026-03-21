<?php

declare(strict_types=1);

namespace League\Route;

use FastRoute\{DataGenerator, RouteCollector, RouteParser};
use InvalidArgumentException;
use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Strategy\{ApplicationStrategy, OptionsHandlerInterface, StrategyAwareInterface, StrategyAwareTrait};
use Override;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class Router implements
    MiddlewareAwareInterface,
    RouteCollectionInterface,
    RouterInterface,
    StrategyAwareInterface,
    RequestHandlerInterface,
    RouteConditionHandlerInterface
{
    use MiddlewareAwareTrait;
    use RouteCollectionTrait;
    use RouteConditionHandlerTrait;
    use StrategyAwareTrait;

    protected const string IDENTIFIER_SEPARATOR = "\t";

    /**
     * @var RouteGroup[]
     */
    protected array $groups = [];

    /**
     * @var Route[]
     */
    protected array $namedRoutes = [];

    /**
     * @var array<string, string>
     */
    protected array $patternMatchers = [
        '/{(.+?):number}/'        => '{$1:[0-9]+}',
        '/{(.+?):word}/'          => '{$1:[a-zA-Z]+}',
        '/{(.+?):alphanum_dash}/' => '{$1:[a-zA-Z0-9-_]+}',
        '/{(.+?):slug}/'          => '{$1:[a-z0-9-]+}',
        '/{(.+?):uuid}/'          => '{$1:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}+}',
    ];

    /**
     * @var Route[]
     */
    protected array $routes = [];

    protected bool $routesPrepared = false;

    /**
     * @var array<mixed>
     */
    protected array $routesData = [];

    /** @var array<int, Route> */
    protected array $routeMap = [];

    public function __construct(protected ?RouteCollector $routeCollector = null)
    {
        $this->routeCollector = $this->routeCollector ?? new RouteCollector(
            new RouteParser\Std(),
            new DataGenerator\GroupCountBased(),
        );
    }

    public function addPatternMatcher(string $alias, string $regex): self
    {
        $pattern = '/{(.+?):' . $alias . '}/';
        $regex = '{$1:' . $regex . '}';
        $this->patternMatchers[$pattern] = $regex;
        return $this;
    }

    public function group(string $prefix, callable $group): RouteGroup
    {
        $group = new RouteGroup($prefix, $group, $this);
        $this->groups[] = $group;
        return $group;
    }

    #[Override]
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        if (false === $this->routesPrepared) {
            $this->prepareRoutes($request);
        }

        if ($this->getStrategy() === null) {
            $this->setStrategy(new ApplicationStrategy());
        }

        /** @var Dispatcher $dispatcher */
        $dispatcher = (new Dispatcher($this->routesData))->setStrategy($this->getStrategy());
        $dispatcher->setRouteMap($this->routeMap);

        foreach ($this->getMiddlewareStack() as $middleware) {
            if (is_string($middleware)) {
                $dispatcher->lazyMiddleware($middleware);
                continue;
            }

            $dispatcher->middleware($middleware);
        }

        return $dispatcher->dispatchRequest($request);
    }

    public function getNamedRoute(string $name): Route
    {
        if (!$this->routesPrepared) {
            $this->collectGroupRoutes();
        }

        $this->buildNameIndex();

        if (isset($this->namedRoutes[$name])) {
            return $this->namedRoutes[$name];
        }

        throw new InvalidArgumentException(sprintf('No route of the name (%s) exists', $name));
    }

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->dispatch($request);
    }

    #[Override]
    public function match(ServerRequestInterface $request): MatchResult
    {
        if (false === $this->routesPrepared) {
            $this->prepareRoutes($request);
        }

        if ($this->getStrategy() === null) {
            $this->setStrategy(new ApplicationStrategy());
        }

        $dispatcher = new Dispatcher($this->routesData);
        $dispatcher->setStrategy($this->getStrategy());
        $dispatcher->setRouteMap($this->routeMap);
        return $dispatcher->matchRequest($request);
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
        $path = sprintf('/%s', ltrim($path, '/'));
        $route = new Route($method, $path, $handler);

        $this->routes[] = $route;

        return $route;
    }

    public function prepareRoutes(ServerRequestInterface $request): void
    {
        if ($this->getStrategy() === null) {
            $this->setStrategy(new ApplicationStrategy());
        }

        $this->processGroups();
        $this->buildNameIndex();

        $routes = array_merge(array_values($this->routes), array_values($this->namedRoutes));
        $options = [];
        $index = 0;

        foreach ($routes as $route) {
            if ($route->getStrategy() === null) {
                $route->setStrategy($this->getStrategy());
            }

            $this->routeMap[$index] = $route;
            $this->routeCollector->addRoute($route->getMethod(), $this->parseRoutePath($route->getPath()), $index);
            $index++;

            if (!($this->getStrategy() instanceof OptionsHandlerInterface)) {
                continue;
            }

            $identifier = $route->getScheme() . static::IDENTIFIER_SEPARATOR . $route->getHost()
                . static::IDENTIFIER_SEPARATOR . $route->getPort() . static::IDENTIFIER_SEPARATOR . $route->getPath();

            if ('OPTIONS' === $route->getMethod()) {
                unset($options[$identifier]);
                continue;
            }

            if (!isset($options[$identifier])) {
                $options[$identifier] = [];
            }

            $options[$identifier][] = $route->getMethod();
        }

        $this->buildOptionsRoutes($options, $index);

        $this->routesPrepared = true;
        $this->routesData = $this->routeCollector->getData();
    }

    protected function buildNameIndex(): void
    {
        foreach ($this->routes as $key => $route) {
            if ($route->getName() !== null) {
                unset($this->routes[$key]);
                $this->namedRoutes[$route->getName()] = $route;
            }
        }
    }

    /**
     * @param array<string, array<string>> $options
     */
    protected function buildOptionsRoutes(array $options, int $index = 0): void
    {
        if (!($this->getStrategy() instanceof OptionsHandlerInterface)) {
            return;
        }

        /** @var OptionsHandlerInterface $strategy */
        $strategy = $this->getStrategy();

        foreach ($options as $identifier => $methods) {
            [$scheme, $host, $port, $path] = explode(static::IDENTIFIER_SEPARATOR, $identifier);
            $route = new Route('OPTIONS', $path, $strategy->getOptionsCallable($methods));

            if (!empty($scheme)) {
                $route->setScheme($scheme);
            }

            if (!empty($host)) {
                $route->setHost($host);
            }

            if (!empty($port)) {
                $route->setPort((int) $port);
            }

            $this->routeMap[$index] = $route;
            $this->routeCollector->addRoute($route->getMethod(), $this->parseRoutePath($route->getPath()), $index);
            $index++;
        }
    }

    /** @return array<mixed> */
    public function getRoutesData(): array
    {
        return $this->routesData;
    }

    /** @return array<int, Route> */
    public function getRouteMap(): array
    {
        return $this->routeMap;
    }

    /**
     * @param array<mixed> $data
     * @param array<int, Route> $routeMap
     */
    public function setRoutesData(array $data, array $routeMap): void
    {
        $this->routesData = $data;
        $this->routeMap = $routeMap;
        $this->routesPrepared = true;
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        if (!$this->routesPrepared) {
            $this->collectGroupRoutes();
            $this->buildNameIndex();
        }

        return array_values(array_merge($this->routes, $this->namedRoutes));
    }

    protected function collectGroupRoutes(): void
    {
        foreach ($this->groups as $key => $group) {
            unset($this->groups[$key]);
            $group();
        }
    }

    protected function processGroups(): void
    {
        foreach ($this->groups as $key => $group) {
            unset($this->groups[$key]);
            $group();
        }
    }

    protected function parseRoutePath(string $path): string
    {
        return preg_replace(array_keys($this->patternMatchers), array_values($this->patternMatchers), $path);
    }
}
