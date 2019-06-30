<?php declare(strict_types=1);

namespace League\Route;

use FastRoute\{DataGenerator, RouteCollector, RouteParser};
use InvalidArgumentException;
use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Strategy\{ApplicationStrategy, StrategyAwareInterface, StrategyAwareTrait};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

class Router extends RouteCollector implements
    MiddlewareAwareInterface,
    RouteCollectionInterface,
    StrategyAwareInterface
{
    use MiddlewareAwareTrait;
    use RouteCollectionTrait;
    use StrategyAwareTrait;

    /**
     * @var Route[]
     */
    protected $routes = [];

    /**
     * @var Route[]
     */
    protected $namedRoutes = [];

    /**
     * @var RouteGroup[]
     */
    protected $groups = [];

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
     * Constructor
     *
     * @param RouteParser $parser
     * @param DataGenerator $generator
     */
    public function __construct(?RouteParser $parser = null, ?DataGenerator $generator = null)
    {
        // build parent route collector
        $parser    = $parser ?? new RouteParser\Std;
        $generator = $generator ?? new DataGenerator\GroupCountBased;
        parent::__construct($parser, $generator);
    }

    /**
     * {@inheritdoc}
     */
    public function map(string $method, string $path, $handler): Route
    {
        $path  = sprintf('/%s', ltrim($path, '/'));
        $route = new Route($method, $path, $handler);

        $this->routes[] = $route;

        return $route;
    }

    /**
     * Add a group of routes to the collection
     *
     * @param string   $prefix
     * @param callable $group
     *
     * @return RouteGroup
     */
    public function group(string $prefix, callable $group): RouteGroup
    {
        $group          = new RouteGroup($prefix, $group, $this);
        $this->groups[] = $group;

        return $group;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->getStrategy() === null) {
            $this->setStrategy(new ApplicationStrategy);
        }

        $this->prepRoutes($request);

        /** @var Dispatcher $dispatcher */
        $dispatcher = (new Dispatcher($this->getData()))->setStrategy($this->getStrategy());

        foreach ($this->getMiddlewareStack() as $middleware) {
            if (is_string($middleware)) {
                $dispatcher->lazyMiddleware($middleware);
                continue;
            }

            $dispatcher->middleware($middleware);
        }

        return $dispatcher->dispatchRequest($request);
    }

    /**
     * Prepare all routes, build name index and filter out none matching
     * routes before being passed off to the parser.
     *
     * @param ServerRequestInterface $request
     *
     * @return void
     */
    protected function prepRoutes(ServerRequestInterface $request): void
    {
        $this->processGroups($request);
        $this->buildNameIndex();

        $routes = array_merge(array_values($this->routes), array_values($this->namedRoutes));

        /** @var Route $route */
        foreach ($routes as $key => $route) {
            // check for scheme condition
            $scheme = $route->getScheme();
            if ($scheme !== null && $scheme !== $request->getUri()->getScheme()) {
                continue;
            }

            // check for domain condition
            $host = $route->getHost();
            if ($host !== null && $host !== $request->getUri()->getHost()) {
                continue;
            }

            // check for port condition
            $port = $route->getPort();
            if ($port !== null && $port !== $request->getUri()->getPort()) {
                continue;
            }

            if ($route->getStrategy() === null) {
                $route->setStrategy($this->getStrategy());
            }

            $this->addRoute($route->getMethod(), $this->parseRoutePath($route->getPath()), $route);
        }
    }

    /**
     * Build an index of named routes.
     *
     * @return void
     */
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
     * Process all groups
     *
     * Adds all of the group routes to the collection and determines if the group
     * strategy should be be used.
     *
     * @param ServerRequestInterface $request
     *
     * @return void
     */
    protected function processGroups(ServerRequestInterface $request): void
    {
        $activePath = $request->getUri()->getPath();

        foreach ($this->groups as $key => $group) {
            // we want to determine if we are technically in a group even if the
            // route is not matched so exceptions are handled correctly
            if ($group->getStrategy() !== null
                && strncmp($activePath, $group->getPrefix(), strlen($group->getPrefix())) === 0
            ) {
                $this->setStrategy($group->getStrategy());
            }

            unset($this->groups[$key]);
            $group();
        }
    }

    /**
     * Get a named route
     *
     * @param string $name
     *
     * @return Route
     *
     * @throws InvalidArgumentException when no route of the provided name exists
     */
    public function getNamedRoute(string $name): Route
    {
        $this->buildNameIndex();

        if (isset($this->namedRoutes[$name])) {
            return $this->namedRoutes[$name];
        }

        throw new InvalidArgumentException(sprintf('No route of the name (%s) exists', $name));
    }

    /**
     * Add a convenient pattern matcher to the internal array for use with all routes
     *
     * @param string $alias
     * @param string $regex
     *
     * @return self
     */
    public function addPatternMatcher(string $alias, string $regex): self
    {
        $pattern = '/{(.+?):' . $alias . '}/';
        $regex   = '{$1:' . $regex . '}';

        $this->patternMatchers[$pattern] = $regex;

        return $this;
    }

    /**
     * Replace word patterns with regex in route path
     *
     * @param string $path
     *
     * @return string
     */
    protected function parseRoutePath(string $path): string
    {
        return preg_replace(array_keys($this->patternMatchers), array_values($this->patternMatchers), $path);
    }
}
