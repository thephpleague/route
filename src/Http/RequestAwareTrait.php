<?php

namespace League\Route\Http;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

trait RequestAwareTrait
{
    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * Set a PSR-7 incoming request implementation.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return $this
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Return the request object.
     *
     * @throws \RuntimeException if a request object cannot be determined
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest()
    {
        if (! is_null($this->request)) {
            return $this->request;
        }

        if ($this->getContainer()->has('Psr\Http\Message\ServerRequestInterface')) {
            $this->request = $this->getContainer()->get('Psr\Http\Message\ServerRequestInterface');
            return $this->request;
        }

        throw new RuntimeException('Unable to determine an incoming request object');
    }

    /**
     * @return \Interop\Container\ContainerInterface
     */
    abstract public function getContainer();
}
