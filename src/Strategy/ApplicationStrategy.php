<?php declare(strict_types=1);

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

    /**
     * {@inheritdoc}
     */
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $controller = $route->getCallable($this->getContainer());

        $response = $controller($request, $route->getVars());
        $response = $this->applyDefaultResponseHeaders($response);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
    {
        return $this->throwThrowableMiddleware($exception);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception): MiddlewareInterface
    {
        return $this->throwThrowableMiddleware($exception);
    }

    /**
     * Return a middleware that simply throws an error
     *
     * @param \Throwable $error
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    protected function throwThrowableMiddleware(Throwable $error): MiddlewareInterface
    {
        return new class($error) implements MiddlewareInterface
        {
            protected $error;

            public function __construct(Throwable $error)
            {
                $this->error = $error;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $requestHandler
            ): ResponseInterface {
                throw $this->error;
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionHandler(): MiddlewareInterface
    {
        return $this->getThrowableHandler();
    }

    /**
     * {@inheritdoc}
     */
    public function getThrowableHandler(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface
        {
            /**
             * {@inheritdoc}
             *
             * @throws Throwable
             */
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $requestHandler
            ): ResponseInterface {
                try {
                    return $requestHandler->handle($request);
                } catch (Throwable $e) {
                    throw $e;
                }
            }
        };
    }
}
