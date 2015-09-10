<?php

namespace League\Route;

use Closure;
use FastRoute\Dispatcher as FastRoute;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Strategy\RestfulStrategy;
use RuntimeException;

class Dispatcher extends GroupCountBasedDispatcher
{
    /**
     * Match and dispatch a route matching the given http method and uri.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        $match = parent::dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        if ($match[0] === FastRoute::NOT_FOUND) {
            return $this->handleNotFound($response);
        }

        if ($match[0] === FastRoute::METHOD_NOT_ALLOWED) {
            $allowed = (array) $match[1];
            return $this->handleNotAllowed($response, $allowed);
        }

        return $this->handleFound($match[1], $request, $response, (array) $match[2]);
    }

    /**
     * Handle dispatching of a found route.
     *
     * @param callable                                 $route
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     * @param array                                    $vars
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function handleFound(
        callable               $route,
        ServerRequestInterface $request,
        ResponseInterface      $response,
        array                  $vars
    ) {
        $response = call_user_func_array($route, [$request, $response, $vars]);

        // verify response

        return $response;
    }

    /**
     * Handle a not found route.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @throws \League\Route\Http\Exception\HttpException if a response cannot be built
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function handleNotFound(ResponseInterface $response)
    {
        $exception = new NotFoundException;

        if ($this->getStrategy() instanceof RestfulStrategy) {
            return $exception->buildJsonResponse($response);
        }

        throw $exception;
    }

    /**
     * Handles a not allowed route
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array                               $allowed
     *
     * @throws \League\Route\Http\Exception\MethodNotAllowedException if a response cannot be built
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function handleNotAllowed(array $allowed)
    {
        $exception = new MethodNotAllowedException($allowed);

        if ($this->getStrategy() instanceof RestfulStrategy) {
            return $exception->buildJsonResponse();
        }

        throw $exception;
    }
}
