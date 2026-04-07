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
    RouteConditionHandlerInterface,
    UrlGeneratorInterface
{
    use MiddlewareAwareTrait;
    use RouteCollectionTrait;
    use RouteConditionHandlerTrait;
    use StrategyAwareTrait;

    protected const string IDENTIFIER_SEPARATOR = "\t";

    /** @var RouteGroup[] */
    protected array $groups = [];

    /** @var array<string, array<string>> */
    protected array $middlewareGroups = [];

    /** @var array<string> */
    protected array $pendingMiddlewareGroups = [];

    /** @var int[] */
    protected array $namedRoutes = [];

    /** @var array<string, string> */
    protected array $patternMatchers = [
        '/{(.+?):number}/' => '{$1:[0-9]+}',
        '/{(.+?):word}/' => '{$1:[a-zA-Z]+}',
        '/{(.+?):alphanum_dash}/' => '{$1:[a-zA-Z0-9-_]+}',
        '/{(.+?):slug}/' => '{$1:[a-z0-9-]+}',
        '/{(.+?):uuid}/' => '{$1:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}+}',
    ];

    /** @var Route[] */
    protected array $routes = [];

    protected bool $routesPrepared = false;

    /** @var array<mixed> */
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

    /** @param array<string> $middleware */
    public function defineMiddlewareGroup(string $name, array $middleware): self
    {
        $this->middlewareGroups[$name] = $middleware;
        return $this;
    }

    /** @return array<string> */
    public function getMiddlewareGroup(string $name): array
    {
        if (!array_key_exists($name, $this->middlewareGroups)) {
            throw new InvalidArgumentException(sprintf('No middleware group of the name (%s) exists', $name));
        }

        return $this->middlewareGroups[$name];
    }

    public function middlewareGroup(string $name): self
    {
        $this->pendingMiddlewareGroups[] = $name;
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

        $strategy = $this->getStrategy();
        assert($strategy !== null);

        $dispatcher = new Dispatcher($this->routesData, $strategy, $this->routeMap);

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

        if (isset($this->namedRoutes[$name], $this->routes[$this->namedRoutes[$name]])) {
            return $this->routes[$this->namedRoutes[$name]];
        }

        throw new InvalidArgumentException(sprintf('No route of the name (%s) exists', $name));
    }

    #[Override]
    public function generateUrl(string $name, array $substitutions = []): string
    {
        $route = $this->getNamedRoute($name);
        $rawPath = $route->getPath();
        $mergedSubstitutions = array_merge($route->getVars(), $substitutions);

        $path = $this->resolveOptionalSegments($rawPath, $mergedSubstitutions);
        $resolvedPath = $this->substitutePathParameters($path, $mergedSubstitutions);

        preg_match_all('/\{[^}]+\}/', $resolvedPath, $remainingMatches);

        if (!empty($remainingMatches[0])) {
            $missing = implode(', ', $remainingMatches[0]);
            throw new InvalidArgumentException(
                sprintf('Missing required parameters %s for route "%s"', $missing, $name),
            );
        }

        preg_match_all('/\{([^}:]+)(?::[^}]+)?\}/', $rawPath, $allParamMatches);
        $extraParams = array_diff_key($substitutions, array_flip($allParamMatches[1]));

        if (empty($extraParams)) {
            return $resolvedPath;
        }

        return $resolvedPath . '?' . http_build_query($extraParams);
    }

    /** @param array<string, string> $substitutions */
    private function resolveOptionalSegments(string $path, array $substitutions): string
    {
        $resolveInnermost = static function (string $subject) use ($substitutions): string {
            return (string) preg_replace_callback(
                '/\[([^\[\]]*)\]/',
                static function (array $matches) use ($substitutions): string {
                    preg_match_all('/\{([^}:]+)(?::[^}]+)?\}/', $matches[1], $paramMatches);

                    foreach ($paramMatches[1] as $param) {
                        if (!array_key_exists($param, $substitutions)) {
                            return '';
                        }
                    }

                    return $matches[1];
                },
                $subject,
            );
        };

        while (str_contains($path, '[')) {
            $path = $resolveInnermost($path);
        }

        return $path;
    }

    /** @param array<string, string> $substitutions */
    private function substitutePathParameters(string $path, array $substitutions): string
    {
        return (string) preg_replace_callback(
            '/\{([^}:]+)(?::[^}]+)?\}/',
            static function (array $matches) use ($substitutions): string {
                $param = $matches[1];
                return array_key_exists($param, $substitutions) ? (string) $substitutions[$param] : $matches[0];
            },
            $path,
        );
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

        $strategy = $this->getStrategy();
        assert($strategy !== null);

        $dispatcher = new Dispatcher($this->routesData, $strategy, $this->routeMap);
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

        foreach ($this->pendingMiddlewareGroups as $name) {
            $this->lazyMiddlewares($this->getMiddlewareGroup($name));
        }

        $this->pendingMiddlewareGroups = [];

        $this->collectGroupRoutes();
        $this->buildNameIndex();

        $options = [];
        $index = 0;

        foreach ($this->routes as $route) {
            if ($route->getStrategy() === null) {
                $route->setStrategy($this->getStrategy());
            }

            $this->routeMap[$index] = $route;
            $this->routeCollector->addRoute($route->getMethod(), $this->parseRoutePath($route->getPath()), $index);
            $index++;

            if (!($this->getStrategy() instanceof OptionsHandlerInterface)) {
                continue;
            }

            $identifier = $route->getScheme() . self::IDENTIFIER_SEPARATOR . $route->getHost()
                . self::IDENTIFIER_SEPARATOR . $route->getPort() . self::IDENTIFIER_SEPARATOR . $route->getPath();

            if ('OPTIONS' === $route->getMethod()) {
                unset($options[$identifier]);
                continue;
            }

            if (!isset($options[$identifier])) {
                $options[$identifier] = [];
            }

            $method = $route->getMethod();
            if (is_array($method)) {
                $options[$identifier] = array_merge($options[$identifier], $method);
            } else {
                $options[$identifier][] = $method;
            }
        }

        $this->buildOptionsRoutes($options, $index);

        foreach ($this->routeMap as $route) {
            $route->freeze();
        }

        $this->routesPrepared = true;
        $this->routesData = $this->routeCollector->getData();
    }

    protected function buildNameIndex(): void
    {
        foreach ($this->routes as $key => $route) {
            if ($route->getName() !== null) {
                $this->namedRoutes[$route->getName()] = $key;
            }
        }
    }

    /** @param array<string, array<string>> $options */
    protected function buildOptionsRoutes(array $options, int $index = 0): void
    {
        if (!($this->getStrategy() instanceof OptionsHandlerInterface)) {
            return;
        }

        /** @var OptionsHandlerInterface $strategy */
        $strategy = $this->getStrategy();

        foreach ($options as $identifier => $methods) {
            [$scheme, $host, $port, $path] = explode(self::IDENTIFIER_SEPARATOR, $identifier);
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

    /** @return Route[] */
    public function getRoutes(): array
    {
        if (!$this->routesPrepared) {
            $this->collectGroupRoutes();
            $this->buildNameIndex();
        }

        return $this->routes;
    }

    protected function collectGroupRoutes(): void
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
