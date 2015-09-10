<?php

namespace League\Route\Http\Exception;

use Psr\Http\Message\ResponseInterface;

interface HttpExceptionInterface
{
    /**
     * Return the status code of the http exceptions
     *
     * @return integer
     */
    public function getStatusCode();

    /**
     * Return an array of headers provided when the exception was thrown
     *
     * @return array
     */
    public function getHeaders();

    /**
     * Accepts a response object and builds it in to a json representation of the exception.
     *
     * @param  \Psr\Http\Message\ResponseInterface
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function buildJsonResponse(ResponseInterface $response);
}
