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
                $return = call_user_func_array($route->getCallable(), [$request, $response, $vars]);

                if (! $return instanceof ResponseInterface) {
                    throw new RuntimeException(
                        'Route callables must return an instance of (Psr\Http\Message\ResponseInterface)'
                    );
                }

                $response = $return;
                $response = $next($request, $response);
            } catch (HttpException $e) {
                return $e->buildJsonResponse($response);
            } catch (Exception $e) {
                $body = [
                    'status_code'   => 500,
                    'reason_phrase' => $e->getMessage()
                ];

                $response->getBody()->write(json_encode($body));
                $response = $response->withStatus(500);
            }

            return $response->withAddedHeader('content-type', 'application/json');
        };

        $execChain = (new ExecutionChain)->middleware($middleware);

        // ensure middleware is executed in the order it was added
        $stack = array_reverse($route->getMiddlewareStack());

        foreach ($stack as $middleware) {
            $execChain->middleware($middleware);
        }

        return $execChain;
    }
}
