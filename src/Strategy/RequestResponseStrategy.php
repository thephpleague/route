<?php

namespace League\Route\Strategy;

use RuntimeException;
use Psr\Http\Message\ResponseInterface;

class RequestResponseStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch($controller, array $vars)
    {
        $response = $this->invokeController($controller, [$this->getRequest(), $this->getResponse(), $vars]);

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        throw new RuntimeException(
            'When using the Request -> Response Strategy your controller must ' .
            'return an instance of (Psr\Http\Message\ResponseInterface)'
        );
    }
}
