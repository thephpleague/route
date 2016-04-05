<?php

namespace League\Route\Strategy;

use Exception;
use League\Route\Http\Exception as HttpException;
use League\Route\Middleware\ExecutionChain;
use League\Route\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class JsonStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function getExecutionChain(Route $route, array $vars)
    {
        $middleware = function (
            ServerRequestInterface $request, ResponseInterface $response, callable $next
        ) use (
            $route, $vars
        ) {
            try {
                $response = call_user_func_array($route->getCallable(), [$request, $response, $vars]);

                if (! $response instanceof ResponseInterface) {
                    throw new RuntimeException(
                        'Route callables must return an instance of (Psr\Http\Message\ResponseInterface)'
                    );
                }

                $response = $next($request, $response);
            } catch (HttpException $e) {
                $response = $e->buildJsonResponse($response);
            } catch (Exception $e) {
                $body = [
                    'code'    => 500,
                    'message' => $e->getMessage()
                ];

                $response->getBody()->write(json_encode($body));
                $response = $response->withStatus(500);
            }

            return $response->withAddedHeader('content-type', 'application/json');
        };

        $execChain = (new ExecutionChain)->middleware($middleware);

        foreach ($route->getMiddlewareStack() as $middleware) {
            $execChain->middleware($middleware);
        }

        return $execChain;
    }
}
