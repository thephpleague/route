<?php

namespace League\Route\Strategy;

use RuntimeException;
use Psr\Http\Message\ResponseInterface;

class RequestResponseStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch(callable $controller, array $vars)
    {
        $response = call_user_func_array($controller, [
            $this->getRequest(),
            $this->getResponse(),
            $vars
        ]);

        return $this->determineResponse($response);
    }
}
