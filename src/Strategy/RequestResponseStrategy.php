<?php

namespace League\Route\Strategy;

use League\Route\Route;

class RequestResponseStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch(callable $controller, array $vars, Route $route = null)
    {
        $response = call_user_func_array($controller, [
            $this->getRequest(),
            $this->getResponse(),
            $vars
        ]);

        return $this->determineResponse($response);
    }
}
