<?php

declare(strict_types=1);

namespace League\Route;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function Opis\Closure\{serialize as s, unserialize as u};

class CachedRouter
{
    /**
     * @var callable
     */
    protected $builder;

    /**
     * @var string
     */
    protected $cacheFile;

    /**
     * @var bool
     */
    protected $cacheEnabled;

    public function __construct(callable $builder, string $cacheFile, bool $cacheEnabled = true)
    {
        $this->builder = $builder;
        $this->cacheFile = $cacheFile;
        $this->cacheEnabled = $cacheEnabled;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $router = $this->buildRouter($request);
        return $router->dispatch($request);
    }

    protected function buildRouter(ServerRequestInterface $request): Router
    {
        if (true === $this->cacheEnabled && file_exists($this->cacheFile)) {
            $cache  = file_get_contents($this->cacheFile);
            $router = u($cache, ['allowed_classes' => true]);

            if ($router instanceof Router) {
                return $router;
            }
        }

        $builder = $this->builder;
        $router  = $builder(new Router());

        if (false === $this->cacheEnabled) {
            return $router;
        }

        if ($router instanceof Router) {
            $router->prepareRoutes($request);
            file_put_contents($this->cacheFile, s($router));
            return $router;
        }

        throw new InvalidArgumentException('Invalid Router builder provided to cached router');
    }
}
