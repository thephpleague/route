<?php

declare(strict_types=1);

namespace League\Route\Strategy;

use JsonSerializable;
use League\Route\Http;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use League\Route\{ContainerAwareInterface, ContainerAwareTrait};
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Throwable;

class JsonStrategy extends AbstractStrategy implements ContainerAwareInterface, OptionsHandlerInterface
{
    use ContainerAwareTrait;

    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var int
     */
    protected $jsonFlags;

    public function __construct(ResponseFactoryInterface $responseFactory, int $jsonFlags = 0)
    {
        $this->responseFactory = $responseFactory;
        $this->jsonFlags = $jsonFlags;

        $this->addResponseDecorator(static function (ResponseInterface $response): ResponseInterface {
            if (false === $response->hasHeader('content-type')) {
                $response = $response->withHeader('content-type', 'application/json');
            }

            return $response;
        });
    }

    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception): MiddlewareInterface
    {
        return $this->buildJsonResponseMiddleware($exception);
    }

    public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
    {
        return $this->buildJsonResponseMiddleware($exception);
    }

    public function getOptionsCallable(array $methods): callable
    {
        return function () use ($methods): ResponseInterface {
            $options  = implode(', ', $methods);
            $response = $this->responseFactory->createResponse();
            $response = $response->withHeader('allow', $options);
            return $response->withHeader('access-control-allow-methods', $options);
        };
    }

    public function getThrowableHandler(): MiddlewareInterface
    {
        return new class ($this->responseFactory->createResponse()) implements MiddlewareInterface
        {
            protected $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                try {
                    return $handler->handle($request);
                } catch (Throwable $exception) {
                    $response = $this->response;

                    if ($exception instanceof Http\Exception) {
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

    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $controller = $route->getCallable($this->getContainer());
        $response = $controller($request, $route->getVars());

        if ($this->isJsonSerializable($response)) {
            $body = json_encode($response, $this->jsonFlags);
            $response = $this->responseFactory->createResponse();
            $response->getBody()->write($body);
        }

        return $this->decorateResponse($response);
    }

    protected function buildJsonResponseMiddleware(Http\Exception $exception): MiddlewareInterface
    {
        return new class ($this->responseFactory->createResponse(), $exception) implements MiddlewareInterface
        {
            protected $response;
            protected $exception;

            public function __construct(ResponseInterface $response, Http\Exception $exception)
            {
                $this->response  = $response;
                $this->exception = $exception;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $this->exception->buildJsonResponse($this->response);
            }
        };
    }

    protected function isJsonSerializable($response): bool
    {
        if ($response instanceof ResponseInterface) {
            return false;
        }

        return (is_array($response) || is_object($response) || $response instanceof JsonSerializable);
    }
}
