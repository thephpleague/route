<?php

declare(strict_types=1);

namespace League\Route\Cache;

use League\Route\Router as MainRouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface, UriInterface};

class RouterTest extends TestCase
{
    public function testDispatchesFoundRouteThenFromCache(): void
    {
        $cacheFile = __DIR__ . '/routeCache.cache';

        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $uri
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn('/example/route')
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $request
            ->expects($this->exactly(2))
            ->method('withAttribute')
            ->willReturn($request)
        ;

        $cacheStore = new FileCache($cacheFile, 86400);

        $router = new Router(function (MainRouter $router): MainRouter {
            $router->map('GET', '/example/{something}', function (
                ServerRequestInterface $request,
                array $args
            ): ResponseInterface {
                $this->assertSame([
                    'something' => 'route'
                ], $args);

                return $this->createMock(ResponseInterface::class);
            });

            return $router;
        }, $cacheStore);

        $returnedResponse = $router->dispatch($request);
        $this->assertInstanceOf(ResponseInterface::class, $returnedResponse);

        $this->assertFileExists($cacheFile);

        $returnedResponse = $router->dispatch($request);
        $this->assertInstanceOf(ResponseInterface::class, $returnedResponse);

        unlink($cacheFile);
    }

    public function testDispatchesWithCacheDisabled(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $uri->method('getPath')->willReturn('/example/route');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);
        $request->method('withAttribute')->willReturn($request);

        $cache = $this->createMock(\Psr\SimpleCache\CacheInterface::class);
        $cache->expects($this->never())->method('get');
        $cache->expects($this->never())->method('set');

        $router = new Router(function (MainRouter $router): MainRouter {
            $router->map('GET', '/example/{something}', function (
                ServerRequestInterface $request,
                array $args
            ): ResponseInterface {
                return $this->createMock(ResponseInterface::class);
            });

            return $router;
        }, $cache, false);

        $returnedResponse = $router->dispatch($request);
        $this->assertInstanceOf(ResponseInterface::class, $returnedResponse);
    }

    public function testRebuildsRouterWhenSignatureChanges(): void
    {
        $cacheFile = __DIR__ . '/routeCacheSignature.cache';

        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $uri->method('getPath')->willReturn('/example/route');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);
        $request->method('withAttribute')->willReturn($request);

        $cacheStore = new FileCache($cacheFile, 86400);

        $firstRouter = new Router(function (MainRouter $router): MainRouter {
            $router->map('GET', '/example/{something}', function (
                ServerRequestInterface $request,
                array $args
            ): ResponseInterface {
                return $this->createMock(ResponseInterface::class);
            });

            return $router;
        }, $cacheStore);

        $firstRouter->dispatch($request);
        $this->assertFileExists($cacheFile);

        $secondRouter = new Router(function (MainRouter $router): MainRouter {
            $router->map('GET', '/example/{something}', function (
                ServerRequestInterface $request,
                array $args
            ): ResponseInterface {
                return $this->createMock(ResponseInterface::class);
            });
            $router->map('POST', '/other', function (
                ServerRequestInterface $request,
                array $args
            ): ResponseInterface {
                return $this->createMock(ResponseInterface::class);
            });

            return $router;
        }, $cacheStore);

        $uri2 = $this->createMock(UriInterface::class);
        $uri2->method('getPath')->willReturn('/example/route');

        $request2 = $this->createMock(ServerRequestInterface::class);
        $request2->method('getMethod')->willReturn('GET');
        $request2->method('getUri')->willReturn($uri2);
        $request2->method('withAttribute')->willReturn($request2);

        $returnedResponse = $secondRouter->dispatch($request2);
        $this->assertInstanceOf(ResponseInterface::class, $returnedResponse);

        unlink($cacheFile);
    }

    public function testMatchDelegatesToInnerRouter(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $uri->method('getPath')->willReturn('/example/route');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $cache = $this->createMock(\Psr\SimpleCache\CacheInterface::class);
        $cache->method('get')->willReturn(null);
        $cache->method('set')->willReturn(true);

        $router = new Router(function (MainRouter $router): MainRouter {
            $router->map('GET', '/example/{something}', static function () {
            });
            return $router;
        }, $cache);

        $result = $router->match($request);
        $this->assertTrue($result->isFound());
    }

    public function testHandleDelegatesToDispatch(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $uri->method('getPath')->willReturn('/example/route');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);
        $request->method('withAttribute')->willReturn($request);

        $cache = $this->createMock(\Psr\SimpleCache\CacheInterface::class);
        $cache->method('get')->willReturn(null);
        $cache->method('set')->willReturn(true);

        $router = new Router(function (MainRouter $router): MainRouter {
            $router->map('GET', '/example/{something}', function (
                ServerRequestInterface $request,
                array $args
            ): ResponseInterface {
                return $this->createMock(ResponseInterface::class);
            });
            return $router;
        }, $cache);

        $response = $router->handle($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testRecoverFromCorruptCache(): void
    {
        $cacheFile = sys_get_temp_dir() . '/league_route_corrupt_' . uniqid() . '.cache';
        file_put_contents($cacheFile, 'this is not valid serialized data');

        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $uri->method('getPath')->willReturn('/example/route');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);
        $request->method('withAttribute')->willReturn($request);

        $cacheStore = new FileCache($cacheFile, 86400);

        $router = new Router(function (MainRouter $router): MainRouter {
            $router->map('GET', '/example/{something}', function (
                ServerRequestInterface $request,
                array $args
            ): ResponseInterface {
                return $this->createMock(ResponseInterface::class);
            });
            return $router;
        }, $cacheStore);

        $response = $router->dispatch($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);

        @unlink($cacheFile);
    }

    public function testBuilderModifiesRouterInPlace(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $uri = $this->createMock(UriInterface::class);

        $uri->method('getPath')->willReturn('/example/route');
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);
        $request->method('withAttribute')->willReturn($request);

        $cache = $this->createMock(\Psr\SimpleCache\CacheInterface::class);
        $cache->method('get')->willReturn(null);
        $cache->method('set')->willReturn(true);

        $router = new Router(function (MainRouter $router): void {
            $router->map('GET', '/example/{something}', function (
                ServerRequestInterface $request,
                array $args
            ): ResponseInterface {
                return $this->createMock(\Psr\Http\Message\ResponseInterface::class);
            });
        }, $cache);

        $response = $router->dispatch($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
