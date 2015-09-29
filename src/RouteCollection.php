<?php

namespace League\Route;

use FastRoute\DataGenerator;
use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdRouteParser;
use Interop\Container\ContainerInterface;
use League\Container\Container;
use League\Route\Strategy\RequestResponseStrategy;
use League\Route\Strategy\StrategyAwareInterface;
use League\Route\Strategy\StrategyAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteCollection extends RouteCollector implements StrategyAwareInterface, RouteCollectionInterface
{
    use RouteCollectionMapTrait;
    use StrategyAwareTrait;

    /**
     * @var \Interop\Container\ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var array
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
        '/{(.+?):slug}/'          => '{$1:[a-z0-9-]+}'
    ];

    /**
     * Constructor.
     *
     * @param \Interop\Container\ContainerInterface $container
     * @param \FastRoute\RouteParser                $parser
     * @param \FastRoute\DataGenerator              $generator
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
        $path = str_pad($path, 1, '/', STR_PAD_LEFT);

        $route = (new Route)->setMethods((array) $method)
                            ->setPath($this->parseRoutePath($path))
                            ->setCallable($handler);

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
        $group = new RouteGroup($prefix, $group, $this);

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

        return $dispatcher->handle($request, $response);
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
            $this->setStrategy(new RequestResponseStrategy);
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
        foreach ($this->routes as $key => $route) {
            // check for scheme condition
            if (! is_null($route->getScheme()) && $route->getScheme() !== $request->getUri()->getScheme()) {
                unset($this->routes[$key]);
                continue;
            }

            // check for domain condition
            if (! is_null($route->getHost()) && $route->getHost() !== $request->getUri()->getHost()) {
                unset($this->routes[$key]);
                continue;
            }

            $route->setContainer($this->container);

            if (is_null($route->getStrategy())) {
                $route->setStrategy($this->getStrategy());
            }

            if (! is_null($route->getName())) {
                $this->namedRoutes[$route->getName()] = $route;
            }

            unset($this->routes[$key]);

            $this->addRoute(
                $route->getMethods(),
                $this->parseRouteString($route->getPath()),
                [$route, 'dispatch']
            );
        }

        foreach ($this->groups as $group) {
            $group();
        }
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
