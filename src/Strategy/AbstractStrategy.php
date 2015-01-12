<?php

namespace League\Route\Strategy;

use League\Container\Container;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractStrategy implements ContainerAwareInterface
{
    /**
     * @var \League\Container\ContainerInterface
     */
    protected $container;

    /**
     * Set a container
     *
     * @param \League\Container\ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get the container
     *
     * @return \League\Container\ContainerInterface
     */
    public function getContainer()
    {
        return (is_null($this->container)) ? new Container : $this->container;
    }

    /**
     * Invoke a controller action
     *
     * @param  string|array|\Closure $controller
     * @param  array                 $vars
     * @return \League\Http\ResponseInterface
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
     * Attempt to build a response
     *
     * @param  mixed $response
     * @return \League\Http\ResponseInterface
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
}
