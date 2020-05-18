<?php declare(strict_types=1);

namespace League\Route;

use FastRoute\{DataGenerator, RouteCollector, RouteParser};
use InvalidArgumentException;
use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Strategy\{ApplicationStrategy, StrategyAwareInterface, StrategyAwareTrait};
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

class Router implements
    MiddlewareAwareInterface,
    RouteCollectionInterface,
    StrategyAwareInterface,
    RequestHandlerInterface
{
    use MiddlewareAwareTrait;
    use RouteCollectionTrait;
    use StrategyAwareTrait;

    /**
     * @var RouteGroup[]
     */
    protected $groups = [];

    /**
     * @var Route[]
     */
    protected $namedRoutes = [];

    /**
     * @var array
     */
    protected $patternMatchers = [
        '/{(.+?):number}/'        => '{$1:[0-9]+}',
        '/{(.+?):word}/'          => '{$1:[a-zA-Z]+}',
        '/{(.+?):alphanum_dash}/' => '{$1:[a-zA-Z0-9-_]+}',
        '/{(.+?):slug}/'          => '{$1:[a-z0-9-]+}',
        '/{(.+?):uuid}/'          => '{$1:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}+}'
    ];

    /**
     * @var RouteCollector
     */
    protected $routeCollector;

    /**
     * @var Route[]
     */
    protected $routes = [];

    /**
     * @var bool
     */
    protected $routesPrepared = false;

    /**
     * @var array
     */
    protected $routesData = [];

    public function __construct(?RouteCollector $routeCollector = null)
    {
        $this->routeCollector = $routeCollector ?? new RouteCollector(
            new RouteParser\Std(),
            new DataGenerator\GroupCountBased()
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

    /**
     * {@inheritdoc}
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        if (false === $this->routesPrepared) {
            $this->prepareRoutes($request);
        }

        /** @var Dispatcher $dispatcher */
        $dispatcher = (new Dispatcher($this->routesData))->setStrategy($this->getStrategy());

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
        $this->buildNameIndex();

        if (isset($this->namedRoutes[$name])) {
            return $this->namedRoutes[$name];
        }

        throw new InvalidArgumentException(sprintf('No route of the name (%s) exists', $name));
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->dispatch($request);
    }

    public function map(string $method, string $path, $handler): Route
    {
        $path  = sprintf('/%s', ltrim($path, '/'));
        $route = new Route($method, $path, $handler);

        $this->routes[] = $route;

        return $route;
    }

    public function prepareRoutes(ServerRequestInterface $request): void
    {
        if ($this->getStrategy() === null) {
            $this->setStrategy(new ApplicationStrategy());
        }

        $this->processGroups($request);
        $this->buildNameIndex();

        $routes = array_merge(array_values($this->routes), array_values($this->namedRoutes));

        /** @var Route $route */
        foreach ($routes as $key => $route) {
            if ($route->getStrategy() === null) {
                $route->setStrategy($this->getStrategy());
            }

            $this->routeCollector->addRoute($route->getMethod(), $this->parseRoutePath($route->getPath()), $route);
        }

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

    protected function processGroups(ServerRequestInterface $request): void
    {
        $activePath = $request->getUri()->getPath();

        foreach ($this->groups as $key => $group) {
            // we want to determine if we are technically in a group even if the
            // route is not matched so exceptions are handled correctly
            if (
                $group->getStrategy() !== null
                && strncmp($activePath, $group->getPrefix(), strlen($group->getPrefix())) === 0
            ) {
                $this->setStrategy($group->getStrategy());
            }

            unset($this->groups[$key]);
            $group();
        }
    }

    protected function parseRoutePath(string $path): string
    {
        return preg_replace(array_keys($this->patternMatchers), array_values($this->patternMatchers), $path);
    }
}
