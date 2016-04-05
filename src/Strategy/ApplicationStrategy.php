<?php

namespace League\Route\Strategy;

use League\Route\Middleware\ExecutionChain;
use League\Route\Route;
use RuntimeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApplicationStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function getExecutionChain(Route $route, array $vars)
    {
        $middleware = function (
            ServerRequestInterface $request, ResponseInterface $response, callable $next
        ) use (
            $route, $vars
        ) {
            $response = call_user_func_array($route->getCallable(), [$request, $response, $vars]);

            if (! $response instanceof ResponseInterface) {
                throw new RuntimeException(
                    'Route callables must return an instance of (Psr\Http\Message\ResponseInterface)'
                );
            }

            return $next($request, $response);
        };

        $execChain = (new ExecutionChain)->middleware($middleware);

        foreach ($route->getMiddlewareStack() as $middleware) {
            $execChain->middleware($middleware);
        }

        return $execChain;
    }
}
