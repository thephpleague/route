<?php declare(strict_types=1);

namespace League\Route;

use FastRoute\Dispatcher as FastRoute;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Middleware\{MiddlewareAwareInterface, MiddlewareAwareTrait};
use League\Route\Strategy\{StrategyAwareInterface, StrategyAwareTrait};
use OutOfBoundsException;
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
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function dispatchRequest(ServerRequestInterface $request) : ResponseInterface
    {
        $match = $this->dispatch($request->getMethod(), $request->getUri()->getPath());

        switch ($match[0]) {
            case FastRoute::NOT_FOUND:
                $this->setNotFoundDecoratorMiddleware();
                break;
            case FastRoute::METHOD_NOT_ALLOWED:
                $allowed = (array) $match[1];
                $this->setMethodNotAllowedDecoratorMiddleware($allowed);
                break;
            case FastRoute::FOUND:
                $match[1]->setVars($match[2]);
                $this->setFoundMiddleware($match[1]);
                break;
        }

        return $this->handle($request);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $middleware = $this->shiftMiddleware();

        if (is_null($middleware)) {
            throw new OutOfBoundsException('Reached end of middleware stack. Does your controller return a response?');
        }

        return $middleware->process($request, $this);
    }

    /**
     * Set up middleware for a found route
     *
     * @param \League\Route\Route $route
     *
     * @return void
     */
    protected function setFoundMiddleware(Route $route) : void
    {
        if (is_null($route->getStrategy())) {
            $route->setStrategy($this->getStrategy());
        }

        // wrap entire dispatch process in exception handler
        $this->prependMiddleware($route->getStrategy()->getExceptionHandler());

        // add group and route specific middleware
        if ($group = $route->getParentGroup()) {
            $this->middlewares($group->getMiddlewareStack());
        }

        $this->middlewares($route->getMiddlewareStack());

        // add actual route to end of stack
        $this->middleware($route);
    }

    /**
     * Set up middleware for a not found route
     *
     * @return void
     */
    protected function setNotFoundDecoratorMiddleware() : void
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
    protected function setMethodNotAllowedDecoratorMiddleware(array $allowed) : void
    {
        $middleware = $this->getStrategy()->getMethodNotAllowedDecorator(
            new MethodNotAllowedException($allowed)
        );

        $this->prependMiddleware($middleware);
    }
}
