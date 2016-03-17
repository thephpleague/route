<?php

namespace League\Route\Strategy;

use League\Route\Route;

interface StrategyInterface
{
    /**
     * Dispatch the controller, the return value of this method will bubble out and be
     * returned by \League\Route\Dispatcher::dispatch, it does not require a response, however,
     * beware that there is no output buffering by default in the router.
     *
     * This method is passed an optional third argument of the route object itself.
     *
     * @param callable                 $controller
     * @param array                    $vars - named wildcard segments of the matched route
     * @param \League\Route\Route|null $route
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function dispatch(callable $controller, array $vars, Route $route = null);
}
