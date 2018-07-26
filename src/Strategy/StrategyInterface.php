<?php

namespace League\Route\Strategy;

use Exception;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\MiddlewareInterface;

interface StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request) : ResponseInterface;

    /**
     * {@inheritdoc}
     */
    public function getNotFoundDecoratorMiddleware(NotFoundException $exception) : MiddlewareInterface;

    /**
     * {@inheritdoc}
     */
    public function getMethodNotAllowedDecoratorMiddleware(MethodNotAllowedException $exception) : MiddlewareInterface;

    /**
     * {@inheritdoc}
     */
    public function getExceptionHandlerMiddleware() : MiddlewareInterface;
}
