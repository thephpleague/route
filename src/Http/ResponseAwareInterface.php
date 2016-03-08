<?php

namespace League\Route\Http;

use Psr\Http\Message\ResponseInterface;

interface ResponseAwareInterface
{
    /**
     * Set a PSR-7 Response implementation.
     *
     * @param  \Psr\Http\Message\ResponseInterface $request
     *
     * @return $this
     */
    public function setResponse(ResponseInterface $request);

    /**
     * Return the request object.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse();
}
