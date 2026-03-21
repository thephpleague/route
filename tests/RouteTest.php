<?php

declare(strict_types=1);

use League\Route\FreezeableInterface;
use League\Route\Route;
use League\Route\RouteGroup;
use League\Route\Test\Fixture\Controller;
use League\Route\Test\Fixture\MiddlewareController;
use Mockery\MockInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

test('route sets and resolves an invokable class callable', function () {
    $callable = new Controller();
    $route = new Route('GET', '/', $callable);
    expect(is_callable($route->getCallable()))->toBeTrue();
});

test('route sets and resolves a class method array callable', function () {
    $callable = [new Controller(), 'action'];
    $route = new Route('GET', '/', $callable);
    expect(is_callable($route->getCallable()))->toBeTrue();
});

test('route sets and resolves a lazily loaded class method array callable without a container', function () {
    $callable = [new Controller(), 'action'];
    $route = new Route('GET', '/', $callable);
    expect(is_callable($route->getCallable()))->toBeTrue();
});

test('route sets and resolves a lazily loaded class method array callable using a container', function () {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('has')->once()->with(Controller::class)->andReturn(true);
    $container->shouldReceive('get')->once()->with(Controller::class)->andReturn(new Controller());

    $callable = [Controller::class, 'action'];
    $route = new Route('GET', '/', $callable);
    expect(is_callable($route->getCallable($container)))->toBeTrue();
});

test('route sets and resolves a named function callable', function () {
    $callable = 'League\Route\Test\Fixture\namedFunctionCallable';
    $route = new Route('GET', '/', $callable);
    expect(is_callable($route->getCallable()))->toBeTrue();
});

test('route sets and resolves a class method callable as a string via a container', function () {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('has')->once()->with(Controller::class)->andReturn(true);
    $container->shouldReceive('get')->once()->with(Controller::class)->andReturn(new Controller());

    $callable = 'League\Route\Test\Fixture\Controller::action';
    $route = new Route('GET', '/', $callable);
    $newCallable = $route->getCallable($container);

    expect($newCallable)->toBeArray();
    expect($newCallable[0])->toBeInstanceOf(Controller::class);
    expect($newCallable[1])->toEqual('action');
});

test('route sets and resolves a class method callable as a string without a container', function () {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('has')->once()->with(Controller::class)->andReturn(false);

    $callable = 'League\Route\Test\Fixture\Controller::action';
    $route = new Route('GET', '/', $callable);
    $newCallable = $route->getCallable($container);

    expect($newCallable)->toBeArray();
    expect($newCallable[0])->toBeInstanceOf(Controller::class);
    expect($newCallable[1])->toEqual('action');
});

test('route sets and resolves a request handler callable as a string via a container', function () {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('has')->once()->with(MiddlewareController::class)->andReturn(true);
    $container->shouldReceive('get')->once()->with(MiddlewareController::class)->andReturn(new MiddlewareController());

    $callable = 'League\Route\Test\Fixture\MiddlewareController';
    $route = new Route('GET', '/', $callable);
    $newCallable = $route->getCallable($container);

    expect($newCallable)->toBeArray();
    expect($newCallable[0])->toBeInstanceOf(MiddlewareController::class);
    expect($newCallable[1])->toEqual('handle');
});

test('route can set and get all properties', function () {
    $route = new Route('GET', '/something', static function () {});

    /** @var RouteGroup&MockInterface $group */
    $group = Mockery::mock(RouteGroup::class);
    $group->shouldReceive('getPrefix')->once()->andReturn('/group');

    expect($route->setParentGroup($group)->getParentGroup())->toBe($group);
    expect($route->getPath())->toBe('/group/something');
    expect($route->getMethod())->toBe('GET');

    $name = 'a.name';
    expect($route->setName($name)->getName())->toBe($name);

    $scheme = 'http';
    expect($route->setScheme($scheme)->getScheme())->toBe($scheme);

    $host = 'example.com';
    expect($route->setHost($host)->getHost())->toBe($host);

    $vars = ['example', 'something'];
    expect($route->setVars($vars)->getVars())->toBe($vars);

    $port = 8080;
    expect($route->setPort($port)->getPort())->toBe($port);

    $middleware = new class implements MiddlewareInterface {
        public function process(
            ServerRequestInterface $request,
            RequestHandlerInterface $handler,
        ): ResponseInterface {}
    };

    $route->middlewares([$middleware, $middleware]);
    expect($route->getMiddlewareStack())->toBe([$middleware, $middleware]);
});

test('pre-set vars survive after dispatch path vars are applied', function () {
    $route = new Route('GET', '/users/{id}', static function () {});
    $route->setVars(['default_role' => 'viewer']);
    $route->setPathVars(['id' => '42']);

    $vars = $route->getVars();
    expect($vars['default_role'])->toBe('viewer');
    expect($vars['id'])->toBe('42');
});

test('path vars take precedence over default vars when keys conflict', function () {
    $route = new Route('GET', '/users/{id}', static function () {});
    $route->setVars(['id' => 'default']);
    $route->setPathVars(['id' => '42']);

    expect($route->getVars()['id'])->toBe('42');
});

test('setPathVars does not accumulate across multiple calls', function () {
    $route = new Route('GET', '/users/{id}', static function () {});
    $route->setVars(['default_role' => 'viewer']);

    $route->setPathVars(['id' => '42']);
    $route->setPathVars(['id' => '99']);

    $vars = $route->getVars();
    expect($vars['id'])->toBe('99');
    expect($vars['default_role'])->toBe('viewer');
    expect($vars)->toHaveCount(2);
});

test('getPath replaces wildcard segments with provided values', function () {
    $route = new Route('GET', '/a/{wildcard}/and/{wildcardWithMatcher:uuid}', static function () {});

    $path = $route->getPath([
        'wildcard' => 'replaced-wildcard',
        'wildcardWithMatcher' => 'replaced-wildcard-with-matcher',
    ]);

    expect($path)->toBe('/a/replaced-wildcard/and/replaced-wildcard-with-matcher');
});

test('route throws a RuntimeException when processed without a strategy', function () {
    $request = Mockery::mock(ServerRequestInterface::class);
    $requestHandler = Mockery::mock(RequestHandlerInterface::class);

    expect(fn() => (new Route('GET', '/something', static function () {}))->process($request, $requestHandler))
        ->toThrow(RuntimeException::class);
});

test('unfrozen route allows all setters without throwing', function () {
    $route = new Route('GET', '/test', static function () {});

    expect(fn() => $route->setHost('example.com'))->not->toThrow(LogicException::class);
    expect(fn() => $route->setScheme('https'))->not->toThrow(LogicException::class);
    expect(fn() => $route->setPort(443))->not->toThrow(LogicException::class);
    expect(fn() => $route->setName('test.route'))->not->toThrow(LogicException::class);
    expect(fn() => $route->setVars(['key' => 'value']))->not->toThrow(LogicException::class);
    expect(fn() => $route->setPathVars(['id' => '1']))->not->toThrow(LogicException::class);
});

test('frozen route throws LogicException when setHost is called', function () {
    $route = new Route('GET', '/test', static function () {});
    $route->freeze();

    expect(fn() => $route->setHost('example.com'))->toThrow(LogicException::class, 'Cannot modify route after routes have been prepared');
});

test('frozen route throws LogicException when setScheme is called', function () {
    $route = new Route('GET', '/test', static function () {});
    $route->freeze();

    expect(fn() => $route->setScheme('https'))->toThrow(LogicException::class, 'Cannot modify route after routes have been prepared');
});

test('frozen route throws LogicException when setPort is called', function () {
    $route = new Route('GET', '/test', static function () {});
    $route->freeze();

    expect(fn() => $route->setPort(443))->toThrow(LogicException::class, 'Cannot modify route after routes have been prepared');
});

test('frozen route throws LogicException when setName is called', function () {
    $route = new Route('GET', '/test', static function () {});
    $route->freeze();

    expect(fn() => $route->setName('test.route'))->toThrow(LogicException::class, 'Cannot modify route after routes have been prepared');
});

test('frozen route throws LogicException when setStrategy is called', function () {
    $route = new Route('GET', '/test', static function () {});
    $route->freeze();

    $strategy = Mockery::mock(League\Route\Strategy\StrategyInterface::class);

    expect(fn() => $route->setStrategy($strategy))->toThrow(LogicException::class, 'Cannot modify route after routes have been prepared');
});

test('frozen route throws LogicException when middleware is called', function () {
    $route = new Route('GET', '/test', static function () {});
    $route->freeze();

    $middleware = Mockery::mock(MiddlewareInterface::class);

    expect(fn() => $route->middleware($middleware))->toThrow(LogicException::class, 'Cannot modify route after routes have been prepared');
});

test('frozen route throws LogicException when setVars is called', function () {
    $route = new Route('GET', '/test', static function () {});
    $route->freeze();

    expect(fn() => $route->setVars(['key' => 'value']))->toThrow(LogicException::class, 'Cannot modify route after routes have been prepared');
});

test('frozen route allows setPathVars after freezing', function () {
    $route = new Route('GET', '/test/{id}', static function () {});
    $route->freeze();

    expect(fn() => $route->setPathVars(['id' => '42']))->not->toThrow(LogicException::class);
    expect($route->getVars()['id'])->toBe('42');
});

test('route implements FreezeableInterface and isFrozen reflects freeze state', function () {
    $route = new Route('GET', '/test', static function () {});

    expect($route)->toBeInstanceOf(FreezeableInterface::class);
    expect($route->isFrozen())->toBeFalse();

    $route->freeze();

    expect($route->isFrozen())->toBeTrue();
});
