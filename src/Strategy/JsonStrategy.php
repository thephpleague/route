<?php

namespace League\Route\Strategy;

use ArrayObject;
use Exception;
use League\Route\Http\Exception as HttpException;
use League\Route\Route;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class JsonStrategy extends AbstractStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function dispatch(callable $controller, array $vars, Route $route = null)
    {
        try {
            $response = call_user_func_array($controller, [
                $this->getRequest(),
                $vars
            ]);

            if (is_array($response) || $response instanceof ArrayObject) {
                $body     = json_encode($response);
                $response = $this->getResponse();

                if ($response->getBody()->isWritable()) {
                    $response->getBody()->write($body);
                }
            }

            if ($response instanceof ResponseInterface) {
                return $response->withAddedHeader('content-type', 'application/json');
            }
        } catch (HttpException $e) {
            return $e->buildJsonResponse($this->getResponse());
        } catch (Exception $e) {
            $response = $this->getResponse();

            if ($response->getBody()->isWritable()) {
                $response->getBody()->write(json_encode([
                    'status_code'   => 500,
                    'reason_phrase' => $e->getMessage()
                ]));
            }

            return $response
                ->withAddedHeader('content-type', 'application/json')
                ->withStatus(500, $e->getMessage());
        }

        throw new RuntimeException('Unable to build a json response from controller return value.');
    }
}
