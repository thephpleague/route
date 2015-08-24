<?php

namespace League\Route\Strategy;

use League\Route\Http\Exception as HttpException;

class MethodArgumentStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch($controller, array $vars)
    {
        if (is_array($controller)) {
            $controller = [
                $this->container->get($controller[0]),
                $controller[1]
            ];
        }

        $response = $this->container->call($controller, $vars);

        return $this->determineResponse($response);
    }
}
