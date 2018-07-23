<?php

namespace League\Route\Test\Asset;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PSR15AfterMiddleware implements MiddlewareInterface
{

    private $calls = 0;
    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->calls++;
        $response = $handler->handle($request);
        return $response->withHeader('psr15', 'true');
    }

    public function getCalls()
    {
        return $this->calls;
    }
}
