<?php

declare(strict_types=1);

namespace League\Route;

use FastRoute\Dispatcher as FastRouteDispatcher;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Strategy\{StrategyAwareInterface, StrategyAwareTrait, StrategyInterface};
use Override;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class Dispatcher implements
    DispatcherInterface,
    MiddlewareAwareInterface,
    RequestHandlerInterface,
    StrategyAwareInterface
{
    use MiddlewareAwareTrait;
    use StrategyAwareTrait;

    private readonly FastRouteDispatcher $fastRouteDispatcher;

    /** @var array<int, Route> */
    private readonly array $routeMap;

    /**
     * @param array<mixed> $routesData
     * @param array<int, Route> $routeMap
     */
    public function __construct(
        array $routesData,
        StrategyInterface $strategy,
        array $routeMap,
    ) {
        $this->fastRouteDispatcher = new GroupCountBasedDispatcher($routesData);
        $this->setStrategy($strategy);
        $this->routeMap = $routeMap;
    }

    #[Override]
    public function matchRequest(ServerRequestInterface $request): MatchResult
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();
        $match = $this->fastRouteDispatcher->dispatch($method, $uri);

        switch ($match[0]) {
            case FastRouteDispatcher::NOT_FOUND:
                return MatchResult::notFound();
            case FastRouteDispatcher::METHOD_NOT_ALLOWED:
                return MatchResult::methodNotAllowed((array) $match[1]);
            case FastRouteDispatcher::FOUND:
                $route = $this->ensureHandlerIsRoute($match[1], $method, $uri)->setPathVars($match[2]);

                if ($this->isExtraConditionMatch($route, $request)) {
                    return MatchResult::found($route);
                }

                return MatchResult::notFound();
        }

        return MatchResult::notFound();
    }

    #[Override]
    public function dispatchRequest(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->matchRequest($request);

        switch ($result->getStatus()) {
            case MatchStatus::NotFound:
                $this->setNotFoundDecoratorMiddleware();
                break;
            case MatchStatus::MethodNotAllowed:
                $this->setMethodNotAllowedDecoratorMiddleware($result->getAllowedMethods());
                break;
            case MatchStatus::Found:
                $route = $result->getRoute();
                $this->setFoundMiddleware($route);
                $request = $this->requestWithRouteAttributes($request, $route);
                break;
        }

        return $this->handle($request);
    }

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->shiftMiddleware();
        return $middleware->process($request, $this);
    }

    protected function ensureHandlerIsRoute(mixed $matchingHandler, string $httpMethod, string $uri): Route
    {
        if ($matchingHandler instanceof Route) {
            return $matchingHandler;
        }

        if (is_int($matchingHandler) && isset($this->routeMap[$matchingHandler])) {
            return $this->routeMap[$matchingHandler];
        }

        return new Route($httpMethod, $uri, $matchingHandler);
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

        $this->prependMiddleware($strategy->getThrowableHandler());

        if ($group = $route->getParentGroup()) {
            foreach ($group->getMiddlewareStack() as $middleware) {
                $this->middleware($this->resolveMiddleware($middleware, $container));
            }
        }

        foreach ($route->getMiddlewareStack() as $middleware) {
            $this->middleware($this->resolveMiddleware($middleware, $container));
        }

        $this->middleware($route);
    }

    /** @param array<string> $allowed */
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

    private function isExtraConditionMatch(Route $route, ServerRequestInterface $request): bool
    {
        $scheme = $route->getScheme();
        if ($scheme !== null && $scheme !== $request->getUri()->getScheme()) {
            return false;
        }

        $host = $route->getHost();
        if ($host !== null && $host !== $request->getUri()->getHost()) {
            return false;
        }

        $port = $route->getPort();
        return !($port !== null && $port !== $request->getUri()->getPort());
    }
}
