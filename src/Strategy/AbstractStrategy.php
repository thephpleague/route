<?php

namespace League\Route\Strategy;

use League\Container\Container;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractStrategy implements StrategyInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Get the container
     *
     * @return \League\Container\ContainerInterface
     */
    public function getContainer()
    {
        return (is_null($this->container)) ? new Container() : $this->container;
    }

    /**
     * Invoke a controller action
     *
     * @param  array|callable $controller
     * @param  array          $vars
     * @return mixed
     */
    protected function invokeController($controller, array $vars = [])
    {
        $controller = $this->determineController($controller);

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
     * Attempt to create a controller.
     *
     * @param array|callable|string $controller
     *
     * @return array|callable|string
     */
    protected function determineController($controller)
    {
        if (is_array($controller)) {
            $controller = [
                $this->getContainer()->get($controller[0]),
                $controller[1],
            ];
        }

        return $controller;
    }
}
