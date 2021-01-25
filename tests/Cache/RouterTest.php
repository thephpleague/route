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
            ->expects($this->exactly(3))
            ->method('getPath')
            ->willReturn('/example/route')
        ;

        $request
            ->expects($this->exactly(2))
            ->method('getMethod')
            ->willReturn('GET')
        ;

        $request
            ->expects($this->exactly(3))
            ->method('getUri')
            ->willReturn($uri)
        ;

        $request
            ->expects($this->exactly(2))
            ->method('withAttribute')
            ->willReturn($request)
        ;

        $cacheStore = new FileCache($cacheFile, 86400);

        $router = new Router(function (MainRouter $router) {
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
}
