<?php

namespace League\Route\Strategy;

use League\Route\Http\Exception as HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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

        $response = $this->container->call($controller);

        return $this->determineResponse($response);
    }
}
