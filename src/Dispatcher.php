<?php

namespace League\Route;

use Closure;
use FastRoute\Dispatcher as FastRoute;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerInterface;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Strategy\RestfulStrategy;
use League\Route\Strategy\StrategyInterface;
use League\Route\Strategy\StrategyTrait;
use RuntimeException;

class Dispatcher extends GroupCountBasedDispatcher
{
    /**
     * Route strategy functionality
     */
    use StrategyTrait;

    /**
     * @var \League\Container\ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $routes;

    /**
     * Constructor
     *
     * @param \League\Container\ContainerInterface $container
     * @param array                                $routes
     * @param array                                $data
     */
    public function __construct(ContainerInterface $container, array $routes, array $data)
    {
        $this->container = $container;
        $this->routes    = $routes;

        parent::__construct($data);
    }

    /**
     * Match and dispatch a route matching the given http method and uri
     *
     * @param  string $method
     * @param  string $uri
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dispatch($method, $uri)
    {
        $match = parent::dispatch($method, $uri);

        if ($match[0] === FastRoute::NOT_FOUND) {
            return $this->handleNotFound();
        }

        if ($match[0] === FastRoute::METHOD_NOT_ALLOWED) {
            $allowed  = (array) $match[1];
            return $this->handleNotAllowed($allowed);
        }

        $handler  = (isset($this->routes[$match[1]]['callback'])) ? $this->routes[$match[1]]['callback'] : $match[1];
        $strategy = $this->routes[$match[1]]['strategy'];
        $vars     = (array) $match[2];

        return $this->handleFound($route, $vars);
    }

    /**
     * Handle dispatching of a found route.
     *
     * @param  \League\Route\Route $route
     * @param  array               $vars
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function handleFound(Route $route, array $vars)
    {
        $response = $route->dispatch($vars);

        // verify response

        return $response;
    }

    /**
     * Handle a not found route
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleNotFound()
    {
        $exception = new NotFoundException;

        if ($this->getStrategy() instanceof RestfulStrategy) {
            return $exception->getJsonResponse();
        }

        throw $exception;
    }

    /**
     * Handles a not allowed route
     *
     * @param  array $allowed
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleNotAllowed(array $allowed)
    {
        $exception = new MethodNotAllowedException($allowed);

        if ($this->getStrategy() instanceof RestfulStrategy) {
            return $exception->getJsonResponse();
        }

        throw $exception;
    }
}
