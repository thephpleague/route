<?php

declare(strict_types=1);

namespace League\Route\Cache;

use League\Route\MatchResult;
use League\Route\Router as MainRouter;
use League\Route\RouterInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\SimpleCache\CacheInterface;

class Router implements RouterInterface
{
    /** @var callable */
    protected $builder;

    public function __construct(
        callable $builder,
        protected CacheInterface $cache,
        protected bool $cacheEnabled = true,
        protected string $cacheKey = 'league/route/cache'
    ) {
        $this->builder = $builder;
    }

    #[\Override]
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->buildRouter($request)->dispatch($request);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->dispatch($request);
    }

    #[\Override]
    public function match(ServerRequestInterface $request): MatchResult
    {
        return $this->buildRouter($request)->match($request);
    }

    protected function buildRouter(ServerRequestInterface $request): MainRouter
    {
        $router = $this->createRouterFromBuilder();

        if (!$this->cacheEnabled) {
            return $router;
        }

        $cachedData = null;

        try {
            $cached = $this->cache->get($this->cacheKey);
            if (is_string($cached)) {
                $cachedData = unserialize($cached, ['allowed_classes' => false]);
            }
        } catch (\Throwable) {
            $cachedData = null;
        }

        $routeSignature = $this->buildSignatureHash($router);

        if (
            is_array($cachedData)
            && isset($cachedData['signature'], $cachedData['data'])
            && $cachedData['signature'] === $routeSignature
        ) {
            $router->setRoutesData($cachedData['data'], $this->buildRouteMap($router));
            return $router;
        }

        $router->prepareRoutes($request);

        $this->cache->set($this->cacheKey, serialize([
            'signature' => $routeSignature,
            'data' => $router->getRoutesData(),
        ]));

        return $router;
    }

    protected function createRouterFromBuilder(): MainRouter
    {
        $builder = $this->builder;
        $router = new MainRouter();
        $result = $builder($router);

        if ($result instanceof MainRouter) {
            $router = $result;
        }

        return $router;
    }

    protected function buildSignatureHash(MainRouter $router): string
    {
        $routes = $router->getRoutes();
        $signature = '';

        foreach ($routes as $route) {
            $method = $route->getMethod();
            if (is_array($method)) {
                sort($method);
                $method = implode('|', $method);
            }
            $signature .= $method . ':' . $route->getPath() . "\n";
        }

        return md5($signature);
    }

    /** @return array<int, \League\Route\Route> */
    protected function buildRouteMap(MainRouter $router): array
    {
        $routes = $router->getRoutes();
        $routeMap = [];

        foreach ($routes as $index => $route) {
            $routeMap[$index] = $route;
        }

        return $routeMap;
    }
}
