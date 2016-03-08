<?php

namespace League\Route\Http;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

trait ResponseAwareTrait
{
    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * Set a PSR-7 response implementation.
     *
     * @param  \Psr\Http\Message\ResponseInterface $response
     *
     * @return $this
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Return the request object.
     *
     * @throws \RuntimeException if a response object cannot be determined
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        if (! is_null($this->response)) {
            return $this->response;
        }

        if ($this->getContainer()->has('Psr\Http\Message\ResponseInterface')) {
            $this->response = $this->getContainer()->get('Psr\Http\Message\ResponseInterface');
            return $this->response;
        }

        throw new RuntimeException('Unable to determine a response object');
    }

    /**
     * @return \Interop\Container\ContainerInterface
     */
    abstract public function getContainer();
}
