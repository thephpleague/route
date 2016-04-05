<?php

namespace League\Route;

use FastRoute\Dispatcher as FastRoute;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Middleware\ExecutionChain;
use League\Route\Strategy\JsonStrategy;
use League\Route\Strategy\StrategyAwareInterface;
use League\Route\Strategy\StrategyAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Dispatcher extends GroupCountBasedDispatcher implements StrategyAwareInterface
{
    use StrategyAwareTrait;

    /**
     * Match and dispatch a route matching the given http method and
     * uri, retruning an execution chain.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \League\Route\Middleware\ExecutionChain
     */
    public function handle(ServerRequestInterface $request)
    {
        $match = $this->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        if ($match[0] === FastRoute::NOT_FOUND) {
            return $this->handleNotFound();
        }

        if ($match[0] === FastRoute::METHOD_NOT_ALLOWED) {
            $allowed = (array) $match[1];
            return $this->handleNotAllowed($allowed);
        }

        return $this->handleFound($match[1], (array) $match[2]);
    }

    /**
     * Handle dispatching of a found route.
     *
     * @param callable $route
     * @param array    $vars
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function handleFound(callable $route, array $vars)
    {
        return call_user_func_array($route, [$vars]);
    }

    /**
     * Handle a not found route.
     *
     * @return \League\Route\Middleware\ExecutionChain
     */
    protected function handleNotFound()
    {
        $middleware = function (ServerRequestInterface $request, ResponseInterface $response) {
            $exception = new NotFoundException;

            if ($this->getStrategy() instanceof JsonStrategy) {
                return $exception->buildJsonResponse($response);
            }

            throw $exception;
        };

        return (new ExecutionChain)->middleware($middleware);
    }

    /**
     * Handles a not allowed route.
     *
     * @param array $allowed
     *
     * @return \League\Route\Middleware\ExecutionChain
     */
    protected function handleNotAllowed(array $allowed)
    {
        $middleware = function (ServerRequestInterface $request, ResponseInterface $response) {
            $exception = new MethodNotAllowedException($allowed);

            if ($this->getStrategy() instanceof JsonStrategy) {
                return $exception->buildJsonResponse($response);
            }

            throw $exception;
        };

        return (new ExecutionChain)->middleware($middleware);
    }
}
