<?php

namespace League\Route\Strategy;

use League\Route\Route;

interface StrategyInterface
{
    /**
     * Tasked with building a middleware that will dispatch the route callable.
     * That callable should then be attached to an ExecutionChain along with
     * any further middleware that is attached to the route.
     *
     * @param \League\Route\Route $route
     * @param array               $vars - named route arguments
     *
     * @return \League\Route\Middleware\ExecutionChain
     */
    public function getExecutionChain(Route $route, array $vars);
}
