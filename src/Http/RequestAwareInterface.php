<?php

namespace League\Route\Http;

use Psr\Http\Message\ServerRequestInterface;

interface RequestAwareInterface
{
    /**
     * Set a PSR-7 incoming request implementation.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return $this
     */
    public function setRequest(ServerRequestInterface $request);

    /**
     * Return the request object.
     *
     * @throws \RuntimeException if a request object cannot be determined
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest();
}
