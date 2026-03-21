<?php

declare(strict_types=1);

namespace League\Route\Strategy;

use JsonSerializable;
use League\Route\{ContainerAwareInterface, ContainerAwareTrait};
use League\Route\Http;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use Override;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Throwable;

class JsonStrategy extends AbstractStrategy implements ContainerAwareInterface, OptionsHandlerInterface
{
    use ContainerAwareTrait;

    public function __construct(protected ResponseFactoryInterface $responseFactory, protected int $jsonFlags = 0)
    {
        $this->addResponseDecorator(static function (ResponseInterface $response): ResponseInterface {
            if (false === $response->hasHeader('content-type')) {
                $response = $response->withHeader('content-type', 'application/json');
            }

            return $response;
        });
    }

    #[Override]
    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception): MiddlewareInterface
    {
        return $this->buildJsonResponseMiddleware($exception);
    }

    #[Override]
    public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
    {
        return $this->buildJsonResponseMiddleware($exception);
    }

    /** @param array<string> $methods */
    #[Override]
    public function getOptionsCallable(array $methods): callable
    {
        return function (ServerRequestInterface $request, array $vars) use ($methods): ResponseInterface {
            $options  = implode(', ', $methods);
            $response = $this->responseFactory->createResponse();
            $response = $response->withHeader('allow', $options);
            return $response->withHeader('access-control-allow-methods', $options);
        };
    }

    #[Override]
    public function getThrowableHandler(): MiddlewareInterface
    {
        return new class ($this->responseFactory->createResponse()) implements MiddlewareInterface {
            public function __construct(protected readonly ResponseInterface $response) {}

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                try {
                    return $handler->handle($request);
                } catch (Throwable $exception) {
                    $response = $this->response;

                    if ($exception instanceof Http\Exception) {
                        return $exception->buildJsonResponse($response);
                    }

                    $body = json_encode([
                        'status_code'   => 500,
                        'reason_phrase' => $exception->getMessage(),
                    ]);
                    if (is_string($body)) {
                        $response->getBody()->write($body);
                    }

                    $response = $response->withAddedHeader('content-type', 'application/json');
                    $reasonPhrase = strtok($exception->getMessage(), "\n");
                    return $response->withStatus(500, is_string($reasonPhrase) ? $reasonPhrase : '');
                }
            }
        };
    }

    #[Override]
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $controller = $route->getCallable($this->getContainer());
        $response = $controller($request, $route->getVars());

        if ($this->isJsonSerializable($response)) {
            $encodedBody = json_encode($response, $this->jsonFlags);
            $response = $this->responseFactory->createResponse();
            if (is_string($encodedBody)) {
                $response->getBody()->write($encodedBody);
            }
        }

        return $this->decorateResponse($response);
    }

    protected function buildJsonResponseMiddleware(Http\Exception $exception): MiddlewareInterface
    {
        return new class ($this->responseFactory->createResponse(), $exception) implements MiddlewareInterface {
            public function __construct(
                protected readonly ResponseInterface $response,
                protected readonly Http\Exception $exception,
            ) {}

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                return $this->exception->buildJsonResponse($this->response);
            }
        };
    }

    protected function isJsonSerializable(mixed $response): bool
    {
        if ($response instanceof ResponseInterface) {
            return false;
        }

        return (is_array($response) || is_object($response) || $response instanceof JsonSerializable);
    }
}
