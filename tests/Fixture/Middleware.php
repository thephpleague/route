<?php

namespace League\Route\Fixture;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class Middleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $request->withRequestTarget('middleware4');
        return $handler->handle($request);
    }
}
