<?php

declare(strict_types=1);

namespace League\Route;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CachedRouter
{
    /**
     * @var callable
     */
    protected $builder;

    /**
     * @var string
     */
    protected $cachePath;

    public function __construct(callable $builder, string $cachePath)
    {
        $this->builder = $builder;
        $this->cachePath = $cachePath;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $router = $this->buildRouter($request);
        return $router->dispatch($request);
    }

    protected function buildRouter(ServerRequestInterface $request): Router
    {
        if (file_exists($this->cachePath)) {
            $cache  = file_get_contents($this->cachePath);
            $router = unserialize($cache, ['allowed_classes' => true]);

            if ($router instanceof Router) {
                return $router;
            }
        }

        $builder = $this->builder;
        $router  = $builder(new Router());

        if ($router instanceof Router) {
            $router->prepareRoutes($request);
            file_put_contents($this->cachePath, serialize($router));
            return $router;
        }

        throw new InvalidArgumentException('Invalid Router builder provided to cached router');
    }
}
