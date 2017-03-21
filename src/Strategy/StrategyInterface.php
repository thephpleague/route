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
