<?php

declare(strict_types=1);

namespace League\Route\Http\Exception;

use Psr\Http\Message\ResponseInterface;

interface HttpExceptionInterface
{
    public function buildJsonResponse(ResponseInterface $response): ResponseInterface;

    /** @return array<string, string> */
    public function getHeaders(): array;
    public function getStatusCode(): int;
}
