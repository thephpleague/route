<?php

/**
 * The cached router is currently in BETA and not recommended for production code.
 *
 * Please feel free to heavily test and report any issues as an issue on the GitHub repository.
 */

declare(strict_types=1);

namespace League\Route\Cache;

use InvalidArgumentException;
use League\Route\Router as MainRouter;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\SimpleCache\CacheInterface;

use function Opis\Closure\{serialize as s, unserialize as u};

class Router
{
    /**
     * @var callable
     */
    protected $builder;

    protected int $ttl;

    public function __construct(
        callable $builder,
        protected CacheInterface $cache,
        protected bool $cacheEnabled = true,
        protected string $cacheKey = 'league/route/cache'
    ) {
        $this->builder = $builder;
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $router = $this->buildRouter($request);
        return $router->dispatch($request);
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function buildRouter(ServerRequestInterface $request): MainRouter
    {
        if (true === $this->cacheEnabled && $cache = $this->cache->get($this->cacheKey)) {
            $router = u($cache, ['allowed_classes' => true]);

            if ($router instanceof MainRouter) {
                return $router;
            }
        }

        $builder = $this->builder;
        $router = $builder(new MainRouter());

        if (false === $this->cacheEnabled) {
            return $router;
        }

        if ($router instanceof MainRouter) {
            $router->prepareRoutes($request);
            $this->cache->set($this->cacheKey, s($router));
            return $router;
        }

        throw new InvalidArgumentException('Invalid Router builder provided to cached router');
    }
}
