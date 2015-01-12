<?php

namespace League\Route\Strategy;

interface StrategyInterface
{
    /**
     * Dispatch the controller, the return value of this method will bubble out and be
     * returned by \League\Route\Dispatcher::dispatch, it does not require a response, however,
     * beware that there is no output buffering by default in the router
     *
     * $controller can be one of three types but based on the type you can infer what the
     * controller actually is:
     *     - string   (controller is a named function)
     *     - array    (controller is a class method [0 => ClassName, 1 => MethodName])
     *     - \Closure (controller is an anonymous function)
     *
     * @param  string|array|\Closure $controller
     * @param  array                 $vars - named wildcard segments of the matched route
     * @return mixed
     */
    public function dispatch($controller, array $vars);
}
