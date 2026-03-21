<?php

declare(strict_types=1);

namespace League\Route;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

/**
 * @internal This interface is not part of the public API and may change without notice.
 */
interface DispatcherInterface
{
    public function dispatchRequest(ServerRequestInterface $request): ResponseInterface;
    public function matchRequest(ServerRequestInterface $request): MatchResult;
}
