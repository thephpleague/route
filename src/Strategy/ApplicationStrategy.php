<?php

declare(strict_types=1);

namespace League\Route\Strategy;

use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use League\Route\{ContainerAwareInterface, ContainerAwareTrait};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Throwable;

class ApplicationStrategy extends AbstractStrategy implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception): MiddlewareInterface
    {
        return $this->throwThrowableMiddleware($exception);
    }

    public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
    {
        return $this->throwThrowableMiddleware($exception);
    }

    public function getThrowableHandler(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface
        {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                try {
                    return $handler->handle($request);
                } catch (Throwable $e) {
                    throw $e;
                }
            }
        };
    }

    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $controller = $route->getCallable($this->getContainer());
        $response = $controller($request, $route->getVars());
        return $this->decorateResponse($response);
    }

    protected function throwThrowableMiddleware(Throwable $error): MiddlewareInterface
    {
        return new class ($error) implements MiddlewareInterface
        {
            protected $error;

            public function __construct(Throwable $error)
            {
                $this->error = $error;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                throw $this->error;
            }
        };
    }
}
