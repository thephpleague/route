<?php declare(strict_types=1);

namespace League\Route\Strategy;

use Exception;
use League\Route\{ContainerAwareInterface, ContainerAwareTrait};
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class ApplicationStrategy implements ContainerAwareInterface, StrategyInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request) : ResponseInterface
    {
        return call_user_func_array($route->getCallable($this->getContainer()), [$request, $route->getVars()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getNotFoundDecorator(NotFoundException $exception) : MiddlewareInterface
    {
        return $this->throwExceptionMiddleware($exception);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception) : MiddlewareInterface
    {
        return $this->throwExceptionMiddleware($exception);
    }

    /**
     * Return a middleware that simply throws and exception.
     *
     * @param \Exception $exception
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    protected function throwExceptionMiddleware(Exception $exception) : MiddlewareInterface
    {
        return new class($exception) implements MiddlewareInterface
        {
            protected $exception;

            public function __construct(Exception $exception)
            {
                $this->exception = $exception;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $requestHandler
            ) : ResponseInterface {
                throw $this->exception;
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionHandler() : MiddlewareInterface
    {
        return new class implements MiddlewareInterface
        {
            /**
             * {@inheritdoc}
             */
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $requestHandler
            ) : ResponseInterface {
                try {
                    return $requestHandler->handle($request);
                } catch (Exception $e) {
                    throw $e;
                }
            }
        };
    }
}
