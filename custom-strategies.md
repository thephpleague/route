---
layout: default
permalink: /custom-strategies/
title: Custom Strategies
---

# Custom Strategies

You can build your own custom strategy to use in your application as long as it is an implementation of `League\Route\Strategy\StrategyInterface`. A strategy is tasked with:

1. Providing a callable that decorates and returns your controllers response.
2. Providing a callable that will decorate a 404 Not Found Exception and return a response.
3. Providing a callable that will decorate a 405 Method Not Allowed Exception.
4. Providing a callable that will decorate any other exception.

Each of these callables should implement a PSR-7 compatible middleware signature.

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerResponseInterface;

function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
    // ...
    return $response;

    // or
    return $next($request, $response);
}
~~~

This is so that the callable can be appended to the current middleware stack and executed as the request completes.

~~~php
<?php

namespace League\Route\Strategy;

use \Exception;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Route;

interface StrategyInterface
{
    /**
     * Return a PSR-7 compatible middleware callable.
     *
     * ```
     * return function ($request, $response, $next) {
     *     // ...
     *     return $next($request, $response);
     * }
     * ```
     *
     * @param \League\Route\Route $route
     * @param array               $vars - named route arguments
     *
     * @return callable
     */
    public function getCallable(Route $route, array $vars);

    /**
     * Tasked with handling a not found exception, expects a
     * PSR-7 compatible middleware/callable to be returned
     * or for the exception to simply be thrown.
     *
     * ```
     * throw $exception;
     * ```
     * or
     * ```
     * return function ($request, $response) {
     *     // ...
     *     // it is recommended to return the response when decorating an exception
     *     return $response;
     * }
     * ```
     *
     * @param \League\Route\Http\Exception\NotFoundException $exception
     *
     * @return callable
     */
    public function getNotFoundDecorator(NotFoundException $exception);

    /**
     * Taskied with handling a not allowed exception, expects a
     * PSR-7 compatible middleware/callable to be returned
     * or for the exception to simply be thrown.
     *
     * ```
     * throw $exception;
     * ```
     * or
     * ```
     * return function ($request, $response) {
     *     // ...
     *     // it is recommended to return the response when decorating an exception
     *     return $response;
     * }
     * ```
     *
     * @param \League\Route\Http\Exception\MethodNotAllowedException $exception
     *
     * @return callable
     */
    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception);

    /**
     * Taskied with handling a standard exception, expects a
     * PSR-7 compatible middleware/callable to be returned
     * or for the exception to simply be thrown.
     *
     * ```
     * throw $exception;
     * ```
     * or
     * ```
     * return function ($request, $response) {
     *     // ...
     *     // it is recommended to return the response when decorating an exception
     *     return $response;
     * }
     * ```
     *
     * @param \Exception $exception
     *
     * @return callable
     */
    public function getExceptionDecorator(Exception $exception);
}
~~~

When working in production with the `ApplicationStrategy` it is recommended that you extend that strategy and overload the exception decorator methods to gracefully handle errors.
