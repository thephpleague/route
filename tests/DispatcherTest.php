<?php

declare(strict_types=1);

namespace League\Route;

use FastRoute\{DataGenerator, RouteCollector, RouteParser};
use League\Route\{Dispatcher, Route};
use League\Route\Http\Exception\NotFoundException;
use League\Route\Strategy\ApplicationStrategy;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface, UriInterface};

final class DispatcherTest extends TestCase
{
    private $routeCollector;

    public function setUp(): void
    {
        $this->routeCollector = new RouteCollector(
            new RouteParser\Std(),
            new DataGenerator\GroupCountBased()
        );
    }

    public function testExtendDispatcherNoStrategySetRouteNotFoundExpectRuntimeExceptionThrown(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot determine strategy to use for dispatch of not found route');

        $request  = $this->createMock(ServerRequestInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $get     = 'GET';
        $path    = '/example';
        $handler = static function ($request, $args) use ($response) {
            return $response;
        };

        $route = new Route($get, $path, $handler);

        $this->routeCollector->addRoute($route->getMethod(), $route->getPath(), $route);
        $someRoutesData = $this->routeCollector->getData();

        $myDispatcher = new class ($someRoutesData) extends Dispatcher {
        };

        $request
            ->method('getMethod')
            ->willReturn($get)
        ;

        $request
            ->method('getUri')
            ->willReturn($uri)
        ;

        $uri
            ->method('getPath')
            ->willReturn('/different-path')
        ;

        $myDispatcher->dispatchRequest($request);
    }

    public function testExtendDispatcherNoStrategySetMethodNotAllowedExpectRuntimeExceptionThrown(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot determine strategy to use for dispatch of method not allowed route');

        $request  = $this->createMock(ServerRequestInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $get     = 'GET';
        $path    = '/example';
        $handler = static function ($request, $args) use ($response) {
            return $response;
        };

        $route = new Route($get, $path, $handler);

        $this->routeCollector->addRoute($route->getMethod(), $route->getPath(), $route);
        $someRoutesData = $this->routeCollector->getData();

        $myDispatcher = new class ($someRoutesData) extends Dispatcher {
        };

        $request
            ->method('getMethod')
            ->willReturn('NOT ALLOWED')
        ;

        $request
            ->method('getUri')
            ->willReturn($uri)
        ;

        $uri
            ->method('getPath')
            ->willReturn($path)
        ;

        $myDispatcher->dispatchRequest($request);
    }

    public function testExtendDispatcherNoStrategySetRouteFoundExpectRuntimeExceptionThrown(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot determine strategy to use for dispatch of found route');

        $request  = $this->createMock(ServerRequestInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $get      = 'GET';
        $path     = '/example';
        $handler  = static function ($request, $args) use ($response) {
            return $response;
        };

        $route = new Route($get, $path, $handler);

        $this->routeCollector->addRoute($route->getMethod(), $route->getPath(), $route);
        $someRoutesData = $this->routeCollector->getData();

        $myDispatcher = new class ($someRoutesData) extends Dispatcher {
        };

        $request
            ->method('getMethod')
            ->willReturn($get)
        ;

        $request
            ->method('getUri')
            ->willReturn($uri)
        ;

        $uri
            ->method('getPath')
            ->willReturn($path)
        ;

        $myDispatcher->dispatchRequest($request);
    }

    public function testExtendDispatcherIsExtraConditionSchemeMismatchExpectNotFoundExceptionThrown(): void
    {
        $this->expectException(NotFoundException::class);

        $request  = $this->createMock(ServerRequestInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $get     = 'GET';
        $path    = '/example';
        $handler = static function ($request, $args) use ($response) {
            return $response;
        };

        $route = new Route($get, $path, $handler);

        $this->routeCollector->addRoute($route->getMethod(), $route->getPath(), $route);
        $someRoutesData = $this->routeCollector->getData();

        $myDispatcher = new class ($someRoutesData) extends Dispatcher {
        };

        $myDispatcher->setStrategy(new ApplicationStrategy());

        $request
            ->method('getMethod')
            ->willReturn($get)
        ;

        $request
            ->method('getUri')
            ->willReturn($uri)
        ;

        $uri
            ->method('getPath')
            ->willReturn($path)
        ;

        $uri
            ->method('getScheme')
            ->willReturn('https')
        ;

        $route->setScheme('http');

        $myDispatcher->dispatchRequest($request);
    }

    public function testExtendDispatcherEnsureHandlerConvertedToRouteExpectHandlerResponseReturned(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $uri      = $this->createMock(UriInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $get     = 'GET';
        $path    = '/example';
        $handler = static function ($request, $args) use ($response) {
            return $response;
        };

        $this->routeCollector->addRoute($get, $path, $handler);
        $someRoutesData = $this->routeCollector->getData();

        $myDispatcher = new class ($someRoutesData) extends Dispatcher {
        };

        $myDispatcher->setStrategy(new ApplicationStrategy());

        $request
            ->method('getMethod')
            ->willReturn($get)
        ;

        $request
            ->method('getUri')
            ->willReturn($uri)
        ;

        $uri
            ->method('getPath')
            ->willReturn($path)
        ;

        $actualResponse = $myDispatcher->dispatchRequest($request);

        $this->assertSame($response, $actualResponse);
    }
}
