<?php

declare(strict_types=1);

namespace League\Route\Strategy;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};

interface OptionsHandlerInterface
{
    /**
     * @param array<string> $methods
     * @return callable(ServerRequestInterface, array<string, string>): ResponseInterface
     */
    public function getOptionsCallable(array $methods): callable;
}
