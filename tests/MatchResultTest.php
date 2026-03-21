<?php

declare(strict_types=1);

use League\Route\MatchResult;
use League\Route\MatchStatus;
use League\Route\Route;

test('found result reports correctly and exposes route and status', function () {
    $route = new Route('GET', '/test', static function () {});
    $result = MatchResult::found($route);

    expect($result->isFound())->toBeTrue();
    expect($result->isMethodNotAllowed())->toBeFalse();
    expect($result->getRoute())->toBe($route);
    expect($result->getStatus())->toBe(MatchStatus::Found);
});

test('not found result reports correctly and exposes status', function () {
    $result = MatchResult::notFound();

    expect($result->isFound())->toBeFalse();
    expect($result->isMethodNotAllowed())->toBeFalse();
    expect($result->getStatus())->toBe(MatchStatus::NotFound);
});

test('method not allowed result reports correctly and exposes allowed methods and status', function () {
    $result = MatchResult::methodNotAllowed(['GET', 'POST']);

    expect($result->isFound())->toBeFalse();
    expect($result->isMethodNotAllowed())->toBeTrue();
    expect($result->getAllowedMethods())->toBe(['GET', 'POST']);
    expect($result->getStatus())->toBe(MatchStatus::MethodNotAllowed);
});

test('get route throws a logic exception when the result is not found', function () {
    expect(fn() => MatchResult::notFound()->getRoute())->toThrow(LogicException::class);
});

test('get allowed methods throws a logic exception when the result is found', function () {
    $route = new Route('GET', '/test', static function () {});

    expect(fn() => MatchResult::found($route)->getAllowedMethods())->toThrow(LogicException::class);
});
