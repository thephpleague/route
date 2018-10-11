<?php declare(strict_types=1);

namespace League\Route\Strategy;

use Exception;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\MiddlewareInterface;

interface StrategyInterface
{
    /**
     * Invoke the route callable based on the strategy
     *
     * @param \League\Route\Route                      $route
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request) : ResponseInterface;

    /**
     * Get a middleware that will decorate a NotFoundException
     *
     * @param \League\Route\Http\Exception\NotFoundException $exception
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    public function getNotFoundDecorator(NotFoundException $exception) : MiddlewareInterface;

    /**
     * Get a middleware that will decorate a NotAllowedException
     *
     * @param \League\Route\Http\Exception\MethodNotAllowedException $exception
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception) : MiddlewareInterface;

    /**
     * Get a middleware that will act as an exception handler
     *
     * The middleware must wrap the rest of the middleware stack and catch any
     * thrown exceptions.
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    public function getExceptionHandler() : MiddlewareInterface;

    /**
     * Get a middleware that acts as a throwable handler, it should wrap the rest of the
     * middleware stack and catch any throwables.
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    public function getThrowableHandler() : MiddlewareInterface;
}
