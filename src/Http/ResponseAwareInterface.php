<?php

namespace League\Route\Http;

use Psr\Http\Message\ResponseInterface;

interface RequestAwareInterface
{
    /**
     * Set a PSR-7 Request implementation.
     *
     * @param  \Psr\Http\Message\ResponseInterface $request
     * @return $this
     */
    public function setRequest(ResponseInterface $request);

    /**
     * Return the request object.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getRequest();
}
