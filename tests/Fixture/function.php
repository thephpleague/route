<?php

namespace League\Route\Fixture;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Named function callable.
 *
 * @param ServerRequestInterface $request
 * @param ResponseInterface      $response
 *
 * @return ResponseInterface
 */
function namedFunctionCallable(ServerRequestInterface $request, ResponseInterface $response)
{
    return $response;
}
