<?php

declare(strict_types=1);

namespace League\Route;

use League\Route\Fixture\Controller;
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

    public function testRouteSetsAndResolvesClassMethodCallable(): void
    {
        $callable = [new Controller(), 'action'];
        $route = new Route('GET', '/', $callable);
        $this->assertIsCallable($route->getCallable());
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
        $route    = new Route('GET', '/', $callable);

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

    public function testRouteThrowsExceptionWhenSettingAndResolvingNonCallable(): void
    {
        $this->expectException(RuntimeException::class);
        $route = new Route('GET', '/', new \stdClass());
        $route->getCallable();
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

    public function testGetPathReplacesWildcards(): void
    {
        $route = new Route('GET', '/a/{wildcard}/and/{wildcardWithMatcher:uuid}/and/{wildcardWithMatcherAgain:uuid}', static function () {
        });

        $path = $route->getPath([
            'wildcard'                 => 'replaced-wildcard',
            'wildcardWithMatcher'      => 'replaced-wildcard-with-matcher',
            'wildcardWithMatcherAgain' => 'replaced-wildcard-with-matcher-again',
        ]);

        $this->assertSame('/a/replaced-wildcard/and/replaced-wildcard-with-matcher/and/replaced-wildcard-with-matcher-again', $path);
    }

    public function testGetPathReplacesOptional(): void
    {
        $route = new Route('GET', '/date[/{year:int}]', static function () {
        });

        $path = $route->getPath([
            'year' => '2000'
        ]);

        $this->assertSame('/date/2000', $path);
    }

    public function testGetPathReplacesMissing(): void
    {
        $route = new Route('GET', '/date[/{year:int}]', static function () {
        });

        $path = $route->getPath([]);

        $this->assertSame('/date', $path);
    }

    public function testGetPathReplacesMissingMultiple(): void
    {
        $route = new Route('GET', '/date[/{year}[/{month}]]', static function () {
        });

        $path = $route->getPath([
            'year' => '2000'
        ]);

        $this->assertSame('/date/2000', $path);
    }

    public function testGetPathReplacesOptionalNested(): void
    {
        $route = new Route('GET', '/date[/{year}[/{month}[/{day}]]]', static function () {
        });

        $path = $route->getPath([
            'year' => '2000',
            'month' => '12',
            'day' => '1',
        ]);

        $this->assertSame('/date/2000/12/1', $path);
    }

    public function testGetPathBreaksOnOptionalNested(): void
    {
        $route = new Route('GET', '/date[/{year}[/{month}[/{day}]]]', static function () {
        });

        $path = $route->getPath([
            'year' => '2000',
            'day' => '1',
        ]);

        $this->assertSame('/date/2000', $path);
    }

    public function testGetPathReplacesOptionalNestedMissing(): void
    {
        $route = new Route('GET', '/date[/{year}[/{month}[/{day}]]]', static function () {
        });

        $path = $route->getPath([]);

        $this->assertSame('/date', $path);
    }

    public function testGetPathReplacesOptionalPrefixSuffix(): void
    {
        $route = new Route('GET', '/date[/year-{year}-ca[/month-{month}-ca[/day-{day}-ca]]]', static function () {
        });

        $path = $route->getPath([
            'year' => '2000',
            'month' => '1',
            'day' => '30'
        ]);

        $this->assertSame('/date/year-2000-ca/month-1-ca/day-30-ca', $path);
    }

    public function testGetPathReplacesOptionalMissingPrefixSuffix(): void
    {
        $route = new Route('GET', '/date[/year-{year}-ca[/month-{month}-ca[/day-{day}-ca]]]', static function () {
        });

        $path = $route->getPath([
            'year' => '2000'
        ]);

        $this->assertSame('/date/year-2000-ca', $path);
    }

    public function testGetPathReplacesOptionalMissing(): void
    {
        $route = new Route('GET', '/date[/{year}[/{month}[/{day}]]]', static function () {
        });

        $path = $route->getPath([
            'month' => '1'
        ]);

        $this->assertSame('/date', $path);
    }

    public function testGetPathReplacesOptionalPrefix(): void
    {
        $route = new Route('GET', '/date[-{year}]', static function () {
        });

        $path = $route->getPath([
            'year' => '2000'
        ]);

        $this->assertSame('/date-2000', $path);
    }

    public function testGetPathReplacesOptionalPrefixMissing(): void
    {
        $route = new Route('GET', '/date[-{year}]', static function () {
        });

        $path = $route->getPath([]);

        $this->assertSame('/date', $path);
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
