<?php

namespace League\Route\Strategy;

use Exception;
use Interop\Container\ContainerInterface;
use League\Container\ImmutableContainerAwareInterface;
use League\Container\ImmutableContainerAwareTrait;
use League\Route\Http\RequestAwareInterface;
use League\Route\Http\RequestAwareTrait;
use League\Route\Http\ResponseAwareInterface;
use League\Route\Http\ResponseAwareTrait;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

abstract class AbstractStrategy implements
    ImmutableContainerAwareInterface,
    RequestAwareInterface,
    ResponseAwareInterface
{
    use ImmutableContainerAwareTrait;
    use RequestAwareTrait;
    use ResponseAwareTrait;

    /**
     * Invoke a controller action.
     *
     * @param  string|array|\Closure $controller
     * @param  array                 $vars
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function invokeController($controller, array $vars = [])
    {
        if (is_array($controller)) {
            $controller = [
                $this->getContainer()->get($controller[0]),
                $controller[1]
            ];
        }

        return call_user_func_array($controller, array_values($vars));
    }

    /**
     * Attempt to build a response.
     *
     * @param  mixed $response
     * @throws \RuntimeException if a response cannot be built
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
