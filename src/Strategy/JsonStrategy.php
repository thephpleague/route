<?php

namespace League\Route\Strategy;

<<<<<<< Updated upstream
use \Exception;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
=======
use ArrayObject;
use Exception;
>>>>>>> Stashed changes
use League\Route\Http\Exception as HttpException;
use League\Route\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class JsonStrategy implements StrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function getCallable(Route $route, array $vars)
    {
        return function (ServerRequestInterface $request, ResponseInterface $response, callable $next) use ($route, $vars) {
            $return = call_user_func_array($route->getCallable(), [$request, $response, $vars]);

            if (! $return instanceof ResponseInterface) {
                throw new RuntimeException(
                    'Route callables must return an instance of (Psr\Http\Message\ResponseInterface)'
                );
            }

            $response = $return;
            $response = $next($request, $response);

            return $response->withAddedHeader('content-type', 'application/json');
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getNotFoundDecorator(NotFoundException $exception)
    {
        return function (ServerRequestInterface $request, ResponseInterface $response) use ($exception) {
            return $exception->buildJsonResponse($response);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception)
    {
        return function (ServerRequestInterface $request, ResponseInterface $response) use ($exception) {
            return $exception->buildJsonResponse($response);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionDecorator(Exception $exception)
    {
        return function (ServerRequestInterface $request, ResponseInterface $response) use ($exception) {
            if ($exception instanceof HttpException) {
                return $exception->buildJsonResponse($response);
            }
<<<<<<< Updated upstream
=======
        } catch (HttpException $e) {
            return $e->buildJsonResponse($this->getResponse());
        } catch (Exception $e) {
            $response = $this->getResponse();

            $response->getBody()->write(json_encode([
                'status_code'   => 500,
                'reason_phrase' => $e->getMessage()
            ]));

            return $response->withAddedHeader('content-type', 'application/json')->withStatusCode(500);
        }
>>>>>>> Stashed changes

            $response->getBody()->write(json_encode([
                'status_code'   => 500,
                'reason_phrase' => $exception->getMessage()
            ]));

            $response = $response->withAddedHeader('content-type', 'application/json');
            return $response->withStatus(500, strtok($exception->getMessage(), "\n"));
        };
    }
}
