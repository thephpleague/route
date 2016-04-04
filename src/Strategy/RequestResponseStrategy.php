<?php

namespace League\Route\Strategy;

use League\Route\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestResponseStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch(callable $controller, array $vars, Route $route = null)
    {
        $middleware = function (
            ServerRequestInterface $request, ResponseInterface $response, callable $next
        ) use (
            $controller, $vars
        ) {
            $result   = call_user_func_array($controller, [$request, $response, $vars]);
            $response = $this->determineResponse($result);

            return $next($request, $response);
        };

        return $route->getMiddlewareRunner()->run($middleware, $this->getRequest(), $this->getResponse());
    }
}
