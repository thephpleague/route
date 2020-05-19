<?php

declare(strict_types=1);

namespace League\Route\Fixture;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Controller
{
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface {
        $response = $response->withHeader('invoke', 'true');
        return $next($request, $response);
    }

    public function action(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface {
        $response = $response->withHeader('action', 'true');
        return $next($request, $response);
    }
}
