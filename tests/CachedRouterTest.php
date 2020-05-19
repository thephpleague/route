<?php

declare(strict_types=1);

namespace League\Route;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface, UriInterface};

class CachedRouterTest extends TestCase
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

        $router = new CachedRouter(function (Router $router) {
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
        }, $cacheFile);

        $returnedResponse = $router->dispatch($request);
        $this->assertInstanceOf(ResponseInterface::class, $returnedResponse);

        $this->assertFileExists($cacheFile);

        $returnedResponse = $router->dispatch($request);
        $this->assertInstanceOf(ResponseInterface::class, $returnedResponse);

        unlink($cacheFile);
    }
}
