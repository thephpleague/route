<?php

namespace League\Route\Strategy;

use ArrayObject;
use League\Route\Http\Exception as HttpException;
use League\Route\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class RequestResponseJsonStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch(callable $controller, array $vars, Route $route = null)
    {
        $middleware = function (
            ServerRequestInterface $request, ResponseInterface $response, callable $next
        ) use (
            $controller, $vars
        ) {
            try {
                $result = call_user_func_array($controller, [$request, $response, $vars]);

                if (is_array($result) || $result instanceof ArrayObject) {
                    $body     = json_encode($result);
                    $response = $this->getResponse();

                    if ($response->getBody()->isWritable()) {
                        $response->getBody()->write($body);
                    }
                }

                if ($response instanceof ResponseInterface) {
                    $response = $response->withAddedHeader('content-type', 'application/json');
                    return $next($request, $response);
                }
            } catch (HttpException $e) {
                return $e->buildJsonResponse($this->getResponse());
            }

            throw new RuntimeException('Unable to build a json response from controller return value.');
        };

        return $route->getMiddlewareRunner()->run($middleware, $this->getRequest(), $this->getResponse());
    }
}
