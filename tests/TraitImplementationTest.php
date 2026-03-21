<?php

declare(strict_types=1);

use League\Route\ContainerAwareInterface;
use League\Route\ContainerAwareTrait;
use League\Route\RouteConditionHandlerTrait;
use Psr\Container\ContainerInterface;

test('container aware trait sets and gets the container correctly', function () {
    $class = new class implements ContainerAwareInterface {
        use ContainerAwareTrait;
    };

    $container = Mockery::mock(ContainerInterface::class);

    expect($class->setContainer($container))->toBeInstanceOf(ContainerAwareInterface::class);
    expect($class->getContainer())->toBeInstanceOf(ContainerInterface::class);
});

test('container aware trait throws a runtime exception when used on a non-container-aware instance', function () {
    $class = new class {
        use ContainerAwareTrait;
    };

    $container = Mockery::mock(ContainerInterface::class);

    expect(fn() => $class->setContainer($container))->toThrow(RuntimeException::class);
});

test('route condition handler trait throws a runtime exception when used on a non-route-condition-handler instance', function () {
    $class = new class {
        use RouteConditionHandlerTrait;
    };

    expect(fn() => $class->setHost('example.com'))->toThrow(RuntimeException::class);
});
