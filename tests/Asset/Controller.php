<?php

namespace League\Route\Test\Asset;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Controller
{
    /**
     * Invokable callable.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     * @param callable                                 $next
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $response = $response->withHeader('invoke', 'true');

        return $next($request, $response);
    }

    /**
     * Class method callable.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     * @param callable                                 $next
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function action(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $response = $response->withHeader('action', 'true');

        return $next($request, $response);
    }
}
