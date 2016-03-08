<?php

namespace League\Route\Strategy;

use League\Route\Http\Exception as HttpException;
use League\Route\Route;
use RuntimeException;

class ParamStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch(callable $controller, array $vars, Route $route = null)
    {
        if (method_exists($this->getContainer(), 'call')) {
            $response = $this->getContainer()->call($controller, $vars);

            return $this->determineResponse($response);
        }

        throw new RuntimeException(
            sprintf(
                'To use the parameter strategy, the container must implement the (::call) method. (%s) does not.',
                get_class($this->getContainer())
            )
        );
    }
}
