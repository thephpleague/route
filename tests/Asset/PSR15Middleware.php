<?php

namespace League\Route\Test\Asset;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PSR15Middleware implements MiddlewareInterface
{

    private $calls = 0;
    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->calls++;
        return $handler->handle($request);
    }

    public function getCalls()
    {
        return $this->calls;
    }
}
