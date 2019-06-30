<?php declare(strict_types=1);

namespace League\Route\Http\Exception;

use Psr\Http\Message\ResponseInterface;

interface HttpExceptionInterface
{
    /**
     * Return the status code of the http exceptions
     *
     * @return integer
     */
    public function getStatusCode(): int;

    /**
     * Return an array of headers provided when the exception was thrown
     *
     * @return array
     */
    public function getHeaders(): array;

    /**
     * Accepts a response object and builds it in to a json representation of the exception.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function buildJsonResponse(ResponseInterface $response): ResponseInterface;
}
