<?php declare(strict_types=1);

namespace League\Route;

use FastRoute\Dispatcher as FastRoute;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Strategy\{StrategyAwareInterface, StrategyAwareTrait};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class Dispatcher extends GroupCountBasedDispatcher implements
    MiddlewareAwareInterface,
    RequestHandlerInterface,
    StrategyAwareInterface
{
    use MiddlewareAwareTrait;
    use StrategyAwareTrait;

    /**
     * Dispatch the current route
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function dispatchRequest(ServerRequestInterface $request): ResponseInterface
    {
        $httpMethod = $request->getMethod();
        $uri        = $request->getUri()->getPath();
        $match      = $this->dispatch($httpMethod, $uri);

        switch ($match[0]) {
            case FastRoute::NOT_FOUND:
                $this->setNotFoundDecoratorMiddleware();
                break;
            case FastRoute::METHOD_NOT_ALLOWED:
                $allowed = (array) $match[1];
                $this->setMethodNotAllowedDecoratorMiddleware($allowed);
                break;
            case FastRoute::FOUND:
                $route = $this->ensureHandlerIsRoute($match[1], $httpMethod, $uri)->setVars($match[2]);
                $this->setFoundMiddleware($route);
                break;
        }

        return $this->handle($request);
    }

    /**
     * Ensure handler is a Route, honoring the contract of dispatchRequest.
     *
     * @param Route|mixed $matchingHandler
     * @param string      $httpMethod
     * @param string      $uri
     *
     * @return Route
     *
     */
    private function ensureHandlerIsRoute($matchingHandler, $httpMethod, $uri): Route
    {
        if (is_a($matchingHandler, Route::class)) {
            return $matchingHandler;
        }
        return new Route($httpMethod, $uri, $matchingHandler);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = $this->shiftMiddleware();

        return $middleware->process($request, $this);
    }

    /**
     * Set up middleware for a found route
     *
     * @param Route $route
     *
     * @return void
     */
    protected function setFoundMiddleware(Route $route): void
    {
        if ($route->getStrategy() === null) {
            $route->setStrategy($this->getStrategy());
        }

        $strategy   = $route->getStrategy();
        $container = $strategy instanceof ContainerAwareInterface ? $strategy->getContainer() : null;

        foreach ($this->getMiddlewareStack() as $key => $middleware) {
            $this->middleware[$key] = $this->resolveMiddleware($middleware, $container);
        }

        // wrap entire dispatch process in exception handler
        $this->prependMiddleware($strategy->getExceptionHandler());

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

    /**
     * Set up middleware for a not found route
     *
     * @return void
     */
    protected function setNotFoundDecoratorMiddleware(): void
    {
        $middleware = $this->getStrategy()->getNotFoundDecorator(new NotFoundException);
        $this->prependMiddleware($middleware);
    }

    /**
     * Set up middleware for a not allowed route
     *
     * @param array $allowed
     *
     * @return void
     */
    protected function setMethodNotAllowedDecoratorMiddleware(array $allowed): void
    {
        $middleware = $this->getStrategy()->getMethodNotAllowedDecorator(
            new MethodNotAllowedException($allowed)
        );

        $this->prependMiddleware($middleware);
    }
}
