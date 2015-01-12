<?php

namespace League\Route\Http\Exception;

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
     * Returns a response built from the thrown exception
     *
     * @return \League\Route\Http\Response
     */
    public function getJsonResponse();
}
