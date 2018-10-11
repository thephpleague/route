<?php

namespace League\Route\Fixture;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Named function callable.
 *
 * @param \Psr\Http\Message\ServerRequestInterface $request
 * @param \Psr\Http\Message\ResponseInterface      $response
 *
 * @return \Psr\Http\Message\ResponseInterface
 */
function namedFunctionCallable(ServerRequestInterface $request, ResponseInterface $response)
{
    return $response;
}
