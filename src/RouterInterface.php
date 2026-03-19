<?php

declare(strict_types=1);

namespace League\Route;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface RouterInterface extends RequestHandlerInterface
{
    public function dispatch(ServerRequestInterface $request): ResponseInterface;
    public function match(ServerRequestInterface $request): MatchResult;
}
