<?php

namespace League\Route\Strategy;

use ArrayObject;
use League\Route\Http\Exception as HttpException;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;

class RestfulStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch($controller, array $vars)
    {
        try {
            $response = $this->invokeController($controller, [
                $this->getRequest(),
                $vars
            ]);

            if ($response instanceof JsonResponse) {
                return $response;
            }

            if (is_array($response) || $response instanceof ArrayObject) {
                return new JsonResponse($response);
            }

            throw new RuntimeException(
                'Your controller action must return a valid response for the Restful Strategy. ' .
                'Acceptable responses are of type: [Array], [ArrayObject] and [Symfony\Component\HttpFoundation\JsonResponse]'
            );
        } catch (HttpException $e) {
            return $e->getJsonResponse();
        }
    }
}
