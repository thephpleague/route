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
     * Invoke the given factory to return a response.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function newResponse() : ResponseInterface
    {
        $responseFactory = $this->responseFactory;
        return $responseFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request) : ResponseInterface
    {
        $response = call_user_func_array($route->getCallable($this->getContainer()), [$request, $route->getVars()]);

        if (is_array($response)) {
            $body     = json_encode($response);
            $response = $this->newResponse();
            $response = $response->withStatus(200);
            $response->getBody()->write($body);
        }

        if ($response instanceof ResponseInterface && ! $response->hasHeader('content-type')) {
            $response->withAddedHeader('content-type', 'application/json');
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getNotFoundDecorator(NotFoundException $exception) : MiddlewareInterface
    {
        return $this->buildJsonResponseMiddleware($exception);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception) : MiddlewareInterface
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
        return new class($this->newResponse(), $exception) implements MiddlewareInterface
        {
            protected $response;
            protected $exception;

            public function __construct(ResponseInterface $response, HttpException $exception)
            {
                $this->response  = $response;
                $this->exception = $exception;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $requestHandler
            ) : ResponseInterface {
                return $this->exception->buildJsonResponse($this->response);
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionHandler() : MiddlewareInterface
    {
        return new class($this->newResponse()) implements MiddlewareInterface
        {
            protected $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $requestHandler
            ) : ResponseInterface {
                try {
                    return $requestHandler->handle($request);
                } catch (Exception $exception) {
                    $response = $this->response;

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
