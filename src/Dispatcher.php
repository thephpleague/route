<?php

declare(strict_types=1);

namespace League\Route;

use FastRoute\Dispatcher as FastRoute;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Strategy\{StrategyAwareInterface, StrategyAwareTrait, StrategyInterface};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class Dispatcher extends GroupCountBasedDispatcher implements
    MiddlewareAwareInterface,
    RequestHandlerInterface,
    StrategyAwareInterface
{
    use MiddlewareAwareTrait;
    use StrategyAwareTrait;

    public function dispatchRequest(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $uri    = $request->getUri()->getPath();
        $match  = $this->dispatch($method, $uri);

        switch ($match[0]) {
            case FastRoute::NOT_FOUND:
                $this->setNotFoundDecoratorMiddleware();
                break;
            case FastRoute::METHOD_NOT_ALLOWED:
                $allowed = (array) $match[1];
                $this->setMethodNotAllowedDecoratorMiddleware($allowed);
                break;
            case FastRoute::FOUND:
                $route = $this->ensureHandlerIsRoute($match[1], $method, $uri)->setVars($match[2]);

                if ($this->isExtraConditionMatch($route, $request)) {
                    $this->setFoundMiddleware($route);
                    $request = $this->requestWithRouteAttributes($request, $route);
                    break;
                }

                $this->setNotFoundDecoratorMiddleware();
                break;
        }

        return $this->handle($request);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->shiftMiddleware();
        return $middleware->process($request, $this);
    }

    protected function ensureHandlerIsRoute($matchingHandler, $httpMethod, $uri): Route
    {
        if ($matchingHandler instanceof Route) {
            return $matchingHandler;
        }

        return new Route($httpMethod, $uri, $matchingHandler);
    }

    protected function isExtraConditionMatch(Route $route, ServerRequestInterface $request): bool
    {
        // check for scheme condition
        $scheme = $route->getScheme();
        if ($scheme !== null && $scheme !== $request->getUri()->getScheme()) {
            return false;
        }

        // check for domain condition
        $host = $route->getHost();
        if ($host !== null && $host !== $request->getUri()->getHost()) {
            return false;
        }

        // check for port condition
        $port = $route->getPort();
        if ($port !== null && $port !== $request->getUri()->getPort()) {
            return false;
        }

        return true;
    }

    protected function requestWithRouteAttributes(ServerRequestInterface $request, Route $route): ServerRequestInterface
    {
        $routerParams = $route->getVars();

        foreach ($routerParams as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        return $request;
    }

    protected function setFoundMiddleware(Route $route): void
    {
        if ($route->getStrategy() === null) {
            $strategy = $this->getStrategy();

            if (!($strategy instanceof StrategyInterface)) {
                throw new RuntimeException('Cannot determine strategy to use for dispatch of found route');
            }

            $route->setStrategy($strategy);
        }

        $strategy  = $route->getStrategy();
        $container = $strategy instanceof ContainerAwareInterface ? $strategy->getContainer() : null;

        foreach ($this->getMiddlewareStack() as $key => $middleware) {
            $this->middleware[$key] = $this->resolveMiddleware($middleware, $container);
        }

        // wrap entire dispatch process in exception handler
        $this->prependMiddleware($strategy->getThrowableHandler());

        // add group and route specific middleware
        if ($group = $route->getParentGroup()) {
            foreach ($group->getMiddlewareStack() as $middleware) {
                $this->middleware($this->resolveMiddleware($middleware, $container));
            }
        }

        foreach ($route->getMiddlewareStack() as $middleware) {
            $this->middleware($this->resolveMiddleware($middleware, $container));
        }

        // add actual route to end of stack
        $this->middleware($route);
    }

    protected function setMethodNotAllowedDecoratorMiddleware(array $allowed): void
    {
        $strategy = $this->getStrategy();

        if (!($strategy instanceof StrategyInterface)) {
            throw new RuntimeException('Cannot determine strategy to use for dispatch of method not allowed route');
        }

        $middleware = $strategy->getMethodNotAllowedDecorator(new MethodNotAllowedException($allowed));
        $this->prependMiddleware($middleware);
    }

    protected function setNotFoundDecoratorMiddleware(): void
    {
        $strategy = $this->getStrategy();

        if (!($strategy instanceof StrategyInterface)) {
            throw new RuntimeException('Cannot determine strategy to use for dispatch of not found route');
        }

        $middleware = $strategy->getNotFoundDecorator(new NotFoundException());
        $this->prependMiddleware($middleware);
    }
}
