<?php

namespace League\Route;

use FastRoute;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use League\Dic\ContainerAwareInterface;
use League\Dic\ContainerInterface;

class Dispatcher extends GroupCountBasedDispatcher
{
    /**
     * Route strategy functionality
     */
    use Strategy\StrategyTrait;

    /**
     * @var \League\Dic\ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $routes;

    /**
     * Constructor
     *
     * @param array $routes
     * @param array $data
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
     * @return \League\Http\ResponseInterface
     */
    public function dispatch($method, $uri)
    {
        $match = parent::dispatch($method, $uri);

        switch ($match[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                $response = $this->handleNotFound();
                break;
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowed  = (array) $match[1];
                $response = $this->handleNotAllowed($allowed);
                break;
            case FastRoute\Dispatcher::FOUND:
            default:
                $handler  = (isset($this->routes[$match[1]]['callback'])) ? $this->routes[$match[1]]['callback'] : $match[1];
                $strategy = $this->routes[$match[1]]['strategy'];
                $vars     = (array) $match[2];
                $response = $this->handleFound($handler, $strategy, $vars);
                break;
        }

        return $response;
    }

    /**
     * Handle dispatching of a found route
     *
     * @param  string|\Closure                             $handler
     * @param  integer|\League\Route\CustomStrategyInterface $strategy
     * @param  array                                       $vars
     * @return \League\Http\ResponseInterface
     * @throws RuntimeException
     */
    protected function handleFound($handler, $strategy, array $vars)
    {
        if (is_null($this->getStrategy())) {
            $this->setStrategy($strategy);
        }

        $controller = null;

        // figure out what the controller is
        if (($handler instanceof \Closure) || (is_string($handler) && is_callable($handler))) {
            $controller = $handler;
        }

        if (is_string($handler) && strpos($handler, '::') !== false) {
            $controller = explode('::', $handler);
        }

        // if controller method wasn't specified, throw exception.
        if (! $controller) {
            throw new \RuntimeException('A class method must be provided as a controller. ClassName::methodName');
        }

        // dispatch via strategy
        if ($strategy instanceof ContainerAwareInterface) {
            $strategy->setContainer($this->container);
        }

        return $strategy->dispatch($controller, $vars);
    }

    /**
     * Handle a not found route
     *
     * @return \League\Http\ResponseInterface
     */
    protected function handleNotFound()
    {
        $exception = new Http\Exception\NotFoundException;

        if ($this->getStrategy() instanceof Strategy\RestfulStrategy) {
            return $exception->getJsonResponse();
        }

        throw $exception;
    }

    /**
     * Handles a not allowed route
     *
     * @param  array $allowed
     * @return \League\Http\ResponseInterface
     */
    protected function handleNotAllowed(array $allowed)
    {
        $exception = new Http\Exception\MethodNotAllowedException($allowed);

        if ($this->getStrategy() instanceof Strategy\RestfulStrategy) {
            return $exception->getJsonResponse();
        }

        throw $exception;
    }
}
