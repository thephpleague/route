<?php

namespace League\Route\Strategy;

use Exception;
use Interop\Container\ContainerInterface;
use League\Container\ImmutableContainerAwareInterface;
use League\Container\ImmutableContainerAwareTrait;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

abstract class AbstractStrategy implements
    ImmutableContainerAwareInterface,
    RequestAwareInterface,
    ResponseAwareInterface
{
    use ImmutableContainerAwareTrait;
    use RequestAwareInterface;
    use ResponseAwareInterface;

    /**
     * Attempt to build a response.
     *
     * @param  mixed $response
     *
     * @throws \RuntimeException if a response cannot be built
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function determineResponse($response)
    {
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        try {
            $body     = $response;
            $response = $this->getResponse();

            if ($response->getBody()->isWritable()) {
                $response->getBody()->write($body);
            }
        } catch (Exception $e) {
            throw new RuntimeException('Unable to build Response from controller return value', 0, $e);
        }

        return $response;
    }
}
