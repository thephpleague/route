<?php declare(strict_types=1);

namespace League\Route;

use InvalidArgumentException;
use League\Route\Fixture\Controller;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class RouteTest extends TestCase
{
    /**
     * Asserts that the route can set and resolve an invokable class callable.
     *
     * @return void
     */
    public function testRouteSetsAndResolvesInvokableClassCallable(): void
    {
        $callable = new Controller;
        $route    = new Route('GET', '/', $callable);
        $this->assertInternalType('callable', $route->getCallable());
    }

    /**
     * Asserts that the route can set and resolve a class method callable.
     *
     * @return void
     */
    public function testRouteSetsAndResolvesClassMethodCallable(): void
    {
        $callable = [new Controller, 'action'];
        $route    = new Route('GET', '/', $callable);
        $this->assertInternalType('callable', $route->getCallable());
    }

    /**
     * Asserts that the route can set and resolve a named function callable.
     *
     * @return void
     */
    public function testRouteSetsAndResolvesNamedFunctionCallable(): void
    {
        $callable = 'League\Route\Fixture\namedFunctionCallable';
        $route    = new Route('GET', '/', $callable);
        $this->assertInternalType('callable', $route->getCallable());
    }

    /**
     * Asserts that the route can set and resolve a class method callable via the container.
     *
     * @return void
     */
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
            ->willReturn(new Controller)
        ;

        $callable = 'League\Route\Fixture\Controller::action';
        $route    = new Route('GET', '/', $callable);

        $newCallable = $route->getCallable($container);
        $this->assertCount(2, $newCallable);
        $this->assertInstanceOf(Controller::class, $newCallable[0]);
        $this->assertEquals('action', $newCallable[1]);
    }

    /**
     * Asserts that the route can set and resolve a class method callable without the container.
     *
     * @return void
     */
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
        $this->assertCount(2, $newCallable);
        $this->assertInstanceOf(Controller::class, $newCallable[0]);
        $this->assertEquals('action', $newCallable[1]);
    }

    /**
     * Asserts that the route throws an exception when trying to set and resolve a non callable.
     *
     * @return void
     */
    public function testRouteThrowsExceptionWhenSettingAndResolvingNonCallable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $route = new Route('GET', '/', new \stdClass);
        $route->getCallable();
    }

    /**
     * Asserts that the route can set and get all properties.
     *
     * @return void
     */
    public function testRouteCanSetAndGetAllProperties(): void
    {
        $route = new Route('GET', '/something', function () {
        });

        $group = $this
            ->getMockBuilder(RouteGroup::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->assertSame($group, $route->setParentGroup($group)->getParentGroup());

        $this->assertSame('/something', $route->getPath());
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
                RequestHandlerInterface $requestHandler
            ): ResponseInterface {
            }
        };

        $route->middlewares([$middleware, $middleware]);

        $this->assertSame([
            $middleware, $middleware
        ], $route->getMiddlewareStack());
    }
}
