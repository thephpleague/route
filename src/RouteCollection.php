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

class RouteCollection extends RouteCollector implements StrategyAwareInterface
{
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
     * Add a route to the map.
     *
     * @param array|string $method
     * @param string       $path
     * @param callable     $handler
     *
     * @return \League\Route\Route
     */
    public function map($method, $path, callable $handler)
    {
        $route = (new Route)->setMethods((array) $method)->setPath($path)->setCallable($handler);

        $this->routes[] = $route;

        return $route;
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
        $this->prepRoutes($request);

        if (is_null($this->getStrategy())) {
            $this->setStrategy(new RequestResponseStrategy);
        }

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

            if (! is_null($route->getStrategy())) {
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
    }

    /**
     * Add a route that responds to GET HTTP method.
     *
     * @param string          $route
     * @param string|callable $handler
     *
     * @return \League\Route\Route
     */
    public function get($route, $handler)
    {
        return $this->map('GET', $route, $handler);
    }

    /**
     * Add a route that responds to POST HTTP method.
     *
     * @param string          $route
     * @param string|callable $handler
     *
     * @return \League\Route\Route
     */
    public function post($route, $handler)
    {
        return $this->map('POST', $route, $handler);
    }

    /**
     * Add a route that responds to PUT HTTP method.
     *
     * @param string          $route
     * @param string|callable $handler
     *
     * @return \League\Route\Route
     */
    public function put($route, $handler)
    {
        return $this->map('PUT', $route, $handler);
    }

    /**
     * Add a route that responds to PATCH HTTP method.
     *
     * @param string          $route
     * @param string|callable $handler
     *
     * @return \League\Route\Route
     */
    public function patch($route, $handler)
    {
        return $this->map('PATCH', $route, $handler);
    }

    /**
     * Add a route that responds to DELETE HTTP method.
     *
     * @param string          $route
     * @param string|callable $handler
     *
     * @return \League\Route\Route
     */
    public function delete($route, $handler)
    {
        return $this->map('DELETE', $route, $handler);
    }

    /**
     * Add a route that responds to HEAD HTTP method.
     *
     * @param string          $route
     * @param string|callable $handler
     *
     * @return \League\Route\Route
     */
    public function head($route, $handler)
    {
        return $this->map('HEAD', $route, $handler);
    }

    /**
     * Add a route that responds to OPTIONS HTTP method.
     *
     * @param string          $route
     * @param string|callable $handler
     *
     * @return \League\Route\Route
     */
    public function options($route, $handler)
    {
        return $this->map('OPTIONS', $route, $handler);
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
     * @param string $route
     *
     * @return string
     */
    protected function parseRouteString($route)
    {
        return preg_replace(array_keys($this->patternMatchers), array_values($this->patternMatchers), $route);
    }
}
