<?php

declare(strict_types=1);

namespace League\Route;

use League\Route\Fixture\{Controller, MiddlewareController};
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use RuntimeException;

class RouteTest extends TestCase
{
    public function testRouteSetsAndResolvesInvokableClassCallable(): void
    {
        $callable = new Controller();
        $route = new Route('GET', '/', $callable);
        $this->assertIsCallable($route->getCallable());
    }

    public function testRouteSetsAndResolvesClassMethodArrayCallable(): void
    {
        $callable = [new Controller(), 'action'];
        $route = new Route('GET', '/', $callable);
        $this->assertIsCallable($route->getCallable());
    }

    public function testRouteSetsAndResolvesLazilyLoadedClassMethodArrayCallableWithoutContainer(): void
    {
        $callable = [new Controller(), 'action'];
        $route = new Route('GET', '/', $callable);
        $this->assertIsCallable($route->getCallable());
    }

    public function testRouteSetsAndResolvesLazilyLoadedClassMethodArrayCallableWithContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container
            ->expects($this->once())
            ->method('has')
            ->with($this->equalTo(Controller::class))
            ->willReturn(true)
        ;

        $container
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo(Controller::class))
            ->willReturn(new Controller())
        ;

        $callable = [Controller::class, 'action'];
        $route = new Route('GET', '/', $callable);
        $this->assertIsCallable($route->getCallable($container));
    }

    public function testRouteSetsAndResolvesNamedFunctionCallable(): void
    {
        $callable = 'League\Route\Fixture\namedFunctionCallable';
        $route = new Route('GET', '/', $callable);
        $this->assertIsCallable($route->getCallable());
    }

    public function testRouteSetsAndResolvesClassMethodCallableAsStringViaContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container
            ->expects($this->once())
            ->method('has')
            ->with($this->equalTo(Controller::class))
            ->willReturn(true)
        ;

        $container
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo(Controller::class))
            ->willReturn(new Controller())
        ;

        $callable = 'League\Route\Fixture\Controller::action';
        $route = new Route('GET', '/', $callable);

        $newCallable = $route->getCallable($container);
        $this->assertIsArray($newCallable);
        $this->assertInstanceOf(Controller::class, $newCallable[0]);
        $this->assertEquals('action', $newCallable[1]);
    }

    public function testRouteSetsAndResolvesClassMethodCallableAsStringWithoutContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container
            ->expects($this->once())
            ->method('has')
            ->with($this->equalTo(Controller::class))
            ->willReturn(false)
        ;

        $callable = 'League\Route\Fixture\Controller::action';
        $route    = new Route('GET', '/', $callable);

        $newCallable = $route->getCallable($container);
        $this->assertIsArray($newCallable);
        $this->assertInstanceOf(Controller::class, $newCallable[0]);
        $this->assertEquals('action', $newCallable[1]);
    }

    public function testRouteSetsAndResolvesRequestHandlerCallableAsStringViaContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container
            ->expects($this->once())
            ->method('has')
            ->with($this->equalTo(MiddlewareController::class))
            ->willReturn(true)
        ;

        $container
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo(MiddlewareController::class))
            ->willReturn(new MiddlewareController())
        ;

        $callable = 'League\Route\Fixture\MiddlewareController';
        $route    = new Route('GET', '/', $callable);

        $newCallable = $route->getCallable($container);
        $this->assertIsArray($newCallable);
        $this->assertInstanceOf(MiddlewareController::class, $newCallable[0]);
        $this->assertEquals('handle', $newCallable[1]);
    }

    public function testRouteCanSetAndGetAllProperties(): void
    {
        $route = new Route('GET', '/something', static function () {
        });

        $group = $this
            ->getMockBuilder(RouteGroup::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $group
            ->expects($this->once())
            ->method('getPrefix')
            ->willReturn('/group')
        ;

        $this->assertSame($group, $route->setParentGroup($group)->getParentGroup());

        $this->assertSame('/group/something', $route->getPath());
        $this->assertSame('GET', $route->getMethod());

        $name = 'a.name';
        $this->assertSame($name, $route->setName($name)->getName());

        $scheme = 'http';
        $this->assertSame($scheme, $route->setScheme($scheme)->getScheme());

        $host = 'example.com';
        $this->assertSame($host, $route->setHost($host)->getHost());

        $vars = ['example', 'something'];
        $this->assertSame($vars, $route->setVars($vars)->getVars());

        $port = 8080;
        $this->assertSame($port, $route->setPort($port)->getPort());

        $middleware = new class implements MiddlewareInterface
        {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
            }
        };

        $route->middlewares([$middleware, $middleware]);

        $this->assertSame([
            $middleware, $middleware
        ], $route->getMiddlewareStack());
    }

    public function testPreSetVarsSurviveDispatchPathVars(): void
    {
        $route = new Route('GET', '/users/{id}', static function () {
        });
        $route->setVars(['default_role' => 'viewer']);
        $route->setPathVars(['id' => '42']);

        $vars = $route->getVars();
        $this->assertSame('viewer', $vars['default_role']);
        $this->assertSame('42', $vars['id']);
    }

    public function testPathVarsTakePrecedenceOverDefaultVars(): void
    {
        $route = new Route('GET', '/users/{id}', static function () {
        });
        $route->setVars(['id' => 'default']);
        $route->setPathVars(['id' => '42']);

        $this->assertSame('42', $route->getVars()['id']);
    }

    public function testSetPathVarsDoesNotAccumulateAcrossMultipleCalls(): void
    {
        $route = new Route('GET', '/users/{id}', static function () {
        });
        $route->setVars(['default_role' => 'viewer']);

        $route->setPathVars(['id' => '42']);
        $route->setPathVars(['id' => '99']);

        $vars = $route->getVars();
        $this->assertSame('99', $vars['id']);
        $this->assertSame('viewer', $vars['default_role']);
        $this->assertCount(2, $vars);
    }

    public function testGetPathReplacesWildcards(): void
    {
        $route = new Route('GET', '/a/{wildcard}/and/{wildcardWithMatcher:uuid}', static function () {
        });

        $path = $route->getPath([
            'wildcard'            => 'replaced-wildcard',
            'wildcardWithMatcher' => 'replaced-wildcard-with-matcher',
        ]);

        $this->assertSame('/a/replaced-wildcard/and/replaced-wildcard-with-matcher', $path);
    }

    public function testRouteThrowsWithNoStrategy(): void
    {
        $this->expectException(RuntimeException::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        (new Route('GET', '/something', static function () {
        }))->process($request, $requestHandler);
    }
}
