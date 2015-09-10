<?php

namespace League\Route\Strategy;

use League\Route\Http\Exception as HttpException;

class MethodArgumentStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch(callable $controller, array $vars)
    {
        if (method_exists($this->getContainer()), 'call')) {
            $response = $this->getContainer()->call($controller, $vars);

            return $this->determineResponse($response);
        }

        throw new RuntimeException(
            sprintf(
                'To use the method argument strategy, the container must implement the (::call) method. (%s) does not.',
                get_class($this->getContainer())
            )
        );
    }
}
