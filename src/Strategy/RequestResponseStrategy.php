<?php

namespace League\Route\Strategy;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class RequestResponseStrategy extends AbstractStrategy
{
    /**
     * {@inheritdoc}
     */
    public function dispatch($controller, array $vars)
    {
        $response = $this->invokeController($controller, [
            $this->getContainer()->get('Symfony\Component\HttpFoundation\Request'),
            $this->getContainer()->get('Symfony\Component\HttpFoundation\Response'),
            $vars,
        ]);

        if ($response instanceof Response) {
            return $response;
        }

        throw new RuntimeException(
            'When using the Request -> Response Strategy your controller must ' .
            'return an instance of [Symfony\Component\HttpFoundation\Response]'
        );
    }
}
