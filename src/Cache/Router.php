<?php

/**
 * The cached router is currently in BETA and not recommended for production code.
 *
 * Please feel free to heavily test and report any issues as an issue on the Github repository.
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
    protected const CACHE_KEY = 'league/route/cache';

    /**
     * @var callable
     */
    protected $builder;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var integer
     */
    protected $ttl;

    /**
     * @var bool
     */
    protected $cacheEnabled;

    public function __construct(callable $builder, CacheInterface $cache, bool $cacheEnabled = true)
    {
        $this->builder = $builder;
        $this->cache = $cache;
        $this->cacheEnabled = $cacheEnabled;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $router = $this->buildRouter($request);
        return $router->dispatch($request);
    }

    protected function buildRouter(ServerRequestInterface $request): MainRouter
    {
        if (true === $this->cacheEnabled && $cache = $this->cache->get(static::CACHE_KEY)) {
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
            $this->cache->set(static::CACHE_KEY, s($router));
            return $router;
        }

        throw new InvalidArgumentException('Invalid Router builder provided to cached router');
    }
}
