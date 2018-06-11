<?php

namespace League\Route\Middleware;

use League\Route\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class ExecutionChain implements StackAwareInterface
{
    use StackAwareTrait;

    /**
     * Build and execute the chain.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function execute(ServerRequestInterface $request, ResponseInterface $response)
    {
        $chain = $this->buildExecutionChain();
        return $chain($request, $response);
    }

    /**
     * Build an execution chain.
     *
     * @return callable
     */
    protected function buildExecutionChain()
    {
        $stack = $this->getMiddlewareStack();

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };

        foreach ($stack as $middleware) {
            $next = function (ServerRequestInterface $request, ResponseInterface $response) use ($middleware, $next) {
                if ($middleware instanceof MiddlewareInterface) {
                    $handler = new RequestHandler($next, $response);
                    return $middleware->process($request, $handler);
                } else {
                    return $middleware($request, $response, $next);
                }
            };
        }

        return $next;
    }
}
