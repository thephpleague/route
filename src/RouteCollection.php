<?php

namespace League\Route;

use Exception;
use FastRoute\DataGenerator;
use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdRouteParser;
use InvalidArgumentException;
use League\Container\Container;
use League\Route\Middleware\ExecutionChain;
use League\Route\Middleware\StackAwareInterface as MiddlewareAwareInterface;
use League\Route\Middleware\StackAwareTrait as MiddlewareAwareTrait;
use League\Route\Strategy\ApplicationStrategy;
use League\Route\Strategy\StrategyAwareInterface;
use League\Route\Strategy\StrategyAwareTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteCollection extends RouteCollector implements
    MiddlewareAwareInterface,
    RouteCollectionInterface,
    StrategyAwareInterface
{
    use MiddlewareAwareTrait;
    use RouteCollectionMapTrait;
    use StrategyAwareTrait;

    /**
     * @var \Psr\Container\ContainerInterface
     */
    protected $container;

    /**
     * @var \League\Route\Route[]
     */
    protected $routes = [];

    /**
     * @var \League\Route\Route[]
     */
    protected $namedRoutes = [];

    /**
     * @var \League\Route\RouteGroup[]
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
     * Constructor.
     *
     * @param \Psr\Container\ContainerInterface $container
     * @param \FastRoute\RouteParser            $parser
     * @param \FastRoute\DataGenerator          $generator
     */
    public function __construct(
        ContainerInterface $container = null,
        RouteParser        $parser    = null,
        DataGenerator      $generator = null
    ) {
        $this->container = ($container instanceof ContainerInterface) ? $container : new Container;

        // build parent route collector
        $parser    = ($parser instanceof RouteParser) ? $parser : new StdRouteParser;
        $generator = ($generator instanceof DataGenerator) ? $generator : new GroupCountBasedDataGenerator;
        parent::__construct($parser, $generator);
    }

    /**
     * {@inheritdoc}
     */
    public function map($method, $path, $handler)
    {
        $path  = sprintf('/%s', ltrim($path, '/'));
        $route = (new Route)->setMethods((array) $method)->setPath($path)->setCallable($handler);

        $this->routes[] = $route;

        return $route;
    }

    /**
     * Add a group of routes to the collection.
     *
     * @param string   $prefix
     * @param callable $group
     *
     * @return \League\Route\RouteGroup
     */
    public function group($prefix, callable $group)
    {
        $group          = new RouteGroup($prefix, $group, $this);
        $this->groups[] = $group;

        return $group;
    }

    /**
     * Dispatch the route based on the request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        $dispatcher = $this->getDispatcher($request);
        $execChain  = $dispatcher->handle($request);

        foreach ($this->getMiddlewareStack() as $middleware) {
            $execChain->middleware($middleware);
        }

        try {
            return $execChain->execute($request, $response);
        } catch (Exception $exception) {
            $middleware = $this->getStrategy()->getExceptionDecorator($exception);
            return (new ExecutionChain)->middleware($middleware)->execute($request, $response);
        }
    }

    /**
     * Return a fully configured dispatcher.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \League\Route\Dispatcher
     */
    public function getDispatcher(ServerRequestInterface $request)
    {
        if (is_null($this->getStrategy())) {
            $this->setStrategy(new ApplicationStrategy);
        }

        $this->prepRoutes($request);

        return (new Dispatcher($this->getData()))->setStrategy($this->getStrategy());
    }

    /**
     * Prepare all routes, build name index and filter out none matching
     * routes before being passed off to the parser.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return void
     */
    protected function prepRoutes(ServerRequestInterface $request)
    {
        $this->buildNameIndex();

        $routes = array_merge(array_values($this->routes), array_values($this->namedRoutes));

        foreach ($routes as $key => $route) {
            // check for scheme condition
            if (! is_null($route->getScheme()) && $route->getScheme() !== $request->getUri()->getScheme()) {
                continue;
            }

            // check for domain condition
            if (! is_null($route->getHost()) && $route->getHost() !== $request->getUri()->getHost()) {
                continue;
            }

            $route->setContainer($this->container);

            if (is_null($route->getStrategy())) {
                $route->setStrategy($this->getStrategy());
            }

            $this->addRoute(
                $route->getMethods(),
                $this->parseRoutePath($route->getPath()),
                [$route, 'getExecutionChain']
            );
        }
    }

    /**
     * Build an index of named routes.
     *
     * @return void
     */
    protected function buildNameIndex()
    {
        $this->processGroups();

        foreach ($this->routes as $key => $route) {
            if (! is_null($route->getName())) {
                unset($this->routes[$key]);
                $this->namedRoutes[$route->getName()] = $route;
            }
        }
    }

    /**
     * Process all groups.
     *
     * @return void
     */
    protected function processGroups()
    {
        foreach ($this->groups as $key => $group) {
            unset($this->groups[$key]);
            $group();
        }
    }

    /**
     * Get named route.
     *
     * @param string $name
     *
     * @return \League\Route\Route
     */
    public function getNamedRoute($name)
    {
        $this->buildNameIndex();

        if (array_key_exists($name, $this->namedRoutes)) {
            return $this->namedRoutes[$name];
        }

        throw new InvalidArgumentException(sprintf('No route of the name (%s) exists', $name));
    }

    /**
     * Add a convenient pattern matcher to the internal array for use with all routes.
     *
     * @param string $alias
     * @param string $regex
     *
     * @return void
     */
    public function addPatternMatcher($alias, $regex)
    {
        $pattern = '/{(.+?):' . $alias . '}/';
        $regex   = '{$1:' . $regex . '}';

        $this->patternMatchers[$pattern] = $regex;
    }

    /**
     * Convenience method to convert pre-defined key words in to regex strings.
     *
     * @param string $path
     *
     * @return string
     */
    protected function parseRoutePath($path)
    {
        return preg_replace(array_keys($this->patternMatchers), array_values($this->patternMatchers), $path);
    }
}
