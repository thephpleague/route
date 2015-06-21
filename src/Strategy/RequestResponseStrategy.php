<?php

namespace League\Route\Strategy;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class RequestResponseStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch($controller, array $vars)
    {
        $response = $this->invokeController($controller, [
            $this->getRequest(),
            $this->getContainer()->get('Symfony\Component\HttpFoundation\Response'),
            $vars
        ]);

        if ($response instanceof Response) {
            return $response;
        }

        throw new RuntimeException(
            'When using the Request -> Response Strategy your controller must ' .
            'return an instance of [Symfony\Component\HttpFoundation\Response]'
        );
    }

    /**
     * Get Request either from the container or else create it from globals
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        if ($this->getContainer()->isRegistered('Symfony\Component\HttpFoundation\Request') ||
            $this->getContainer()->isInServiceProvider('Symfony\Component\HttpFoundation\Request') ) {
            return $this->getContainer()->get('Symfony\Component\HttpFoundation\Request');
        } else {
            return $this->getContainer()->get('Symfony\Component\HttpFoundation\Request')->createFromGlobals();
        }
    }
}
