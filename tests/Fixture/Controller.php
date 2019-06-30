<?php

namespace League\Route\Fixture;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Controller
{
    /**
     * Invokable callable.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param callable               $next
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $response = $response->withHeader('invoke', 'true');

        return $next($request, $response);
    }

    /**
     * Class method callable.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable                                 $next
     *
     * @return ResponseInterface
     */
    public function action(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $response = $response->withHeader('action', 'true');

        return $next($request, $response);
    }
}
