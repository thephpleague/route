<?php declare(strict_types=1);

namespace League\Route\Strategy;

use Exception;
use League\Route\{ContainerAwareInterface, ContainerAwareTrait};
use League\Route\Http\Exception as HttpException;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class JsonStrategy implements ContainerAwareInterface, StrategyInterface
{
    use ContainerAwareTrait;

    /**
     * @var callable
     */
    protected $responseFactory;

    /**
     * Construct.
     *
     * @param callable $responseFactory
     */
    public function __construct(callable $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request) : ResponseInterface
    {
        $response = call_user_func_array($route->getCallable($this->getContainer()), [$request, $route->getVars()]);

        if ($response instanceof ResponseInterface && ! $response->hasHeader('content-type')) {
            $response->withHeader('content-type', 'application/json');
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getNotFoundDecoratorMiddleware(NotFoundException $exception) : MiddlewareInterface
    {
        return $this->buildJsonResponseMiddleware($exception);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodNotAllowedDecoratorMiddleware(MethodNotAllowedException $exception) : MiddlewareInterface
    {
        return $this->buildJsonResponseMiddleware($exception);
    }

    /**
     * Return a middleware that simply throws and exception.
     *
     * @param \Exception $exception
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    protected function buildJsonResponseMiddleware(HttpException $exception) : MiddlewareInterface
    {
        return new class($this->responseFactory, $exception) implements MiddlewareInterface
        {
            protected $responseFactory;
            protected $exception;

            public function __construct(callable $responseFactory, HttpException $exception)
            {
                $this->responseFactory = $responseFactory;
                $this->exception       = $exception;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler) : ResponseInterface
            {
                $responseFactory = $this->responseFactory;
                return $this->exception->buildJsonResponse($responseFactory());
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionHandlerMiddleware() : MiddlewareInterface
    {
        return new class($this->responseFactory) implements MiddlewareInterface
        {
            protected $responseFactory;

            public function __construct(callable $responseFactory)
            {
                $this->responseFactory = $responseFactory;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler) : ResponseInterface
            {
                try {
                    return $requestHandler->handle($request);
                } catch (Exception $exception) {
                    $responseFactory = $this->responseFactory;
                    $response = $responseFactory();

                    if ($exception instanceof HttpException) {
                        return $exception->buildJsonResponse($response);
                    }

                    $response->getBody()->write(json_encode([
                        'status_code'   => 500,
                        'reason_phrase' => $exception->getMessage()
                    ]));

                    $response = $response->withAddedHeader('content-type', 'application/json');
                    return $response->withStatus(500, strtok($exception->getMessage(), "\n"));
                }
            }
        };
    }
}
