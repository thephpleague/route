<?php

namespace League\Route\Strategy;

use League\Container\Container;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractStrategy implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Invoke a controller action
     *
     * @param  string|array|\Closure $controller
     * @param  array                 $vars
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function invokeController($controller, array $vars = [])
    {
        if (is_array($controller)) {
            $controller = [
                (is_object($controller[0])) ? $controller[0] : $this->getContainer()->get($controller[0]),
                $controller[1]
            ];
        }

        return call_user_func_array($controller, array_values($vars));
    }

    /**
     * Attempt to build a response
     *
     * @param  mixed $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function determineResponse($response)
    {
        if ($response instanceof Response) {
            return $response;
        }

        try {
            $response = new Response($response);
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to build Response from controller return value', 0, $e);
        }

        return $response;
    }

    /**
     * Get Request either from the container or else create it from globals.
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        if (
            $this->getContainer()->isRegistered('Symfony\Component\HttpFoundation\Request') ||
            $this->getContainer()->isSingleton('Symfony\Component\HttpFoundation\Request') ||
            $this->getContainer()->isInServiceProvider('Symfony\Component\HttpFoundation\Request')
        ) {
            return $this->getContainer()->get('Symfony\Component\HttpFoundation\Request');
        }

        return Request::createFromGlobals();
    }
}
