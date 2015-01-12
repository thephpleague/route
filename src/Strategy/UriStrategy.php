<?php

namespace League\Route\Strategy;

use League\Route\Http\Exception as HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UriStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch($controller, array $vars)
    {
        $response = $this->invokeController($controller, $vars);

        return $this->determineResponse($response);
    }
}
