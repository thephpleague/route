<?php

declare(strict_types=1);

namespace League\Route\Fixture;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

function namedFunctionCallable(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
{
    return $response;
}
