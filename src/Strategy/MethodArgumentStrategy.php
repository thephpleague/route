<?php

namespace League\Route\Strategy;

use League\Route\Http\Exception as HttpException;

class MethodArgumentStrategy extends AbstractStrategy
{
    /**
     * {@inheritdoc}
     */
    public function dispatch($controller, array $vars)
    {
        $controller = $this->determineController($controller);

        $response = $this->getContainer()->call($controller);

        return $this->determineResponse($response);
    }
}
