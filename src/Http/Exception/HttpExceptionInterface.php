<?php

namespace League\Route\Http\Exception;

use Psr\Http\Message\ResponseInterface;

interface HttpExceptionInterface
{
    /**
     * Accepts a response object and builds it in to a json representation of the exception.
     *
     * @param  \Psr\Http\Message\ResponseInterface
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function buildJsonResponse(ResponseInterface $response);
}
