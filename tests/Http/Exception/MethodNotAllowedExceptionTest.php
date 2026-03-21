<?php

declare(strict_types=1);

use League\Route\Http\Exception\MethodNotAllowedException;

test('getAllowedMethods returns the allowed methods array', function () {
    $exception = new MethodNotAllowedException(['GET', 'POST', 'PUT']);

    expect($exception->getAllowedMethods())->toBe(['GET', 'POST', 'PUT']);
});

test('getAllowedMethods returns an empty array when no methods are provided', function () {
    $exception = new MethodNotAllowedException();

    expect($exception->getAllowedMethods())->toBe([]);
});

test('the Allow header is still set correctly alongside getAllowedMethods', function () {
    $exception = new MethodNotAllowedException(['GET', 'POST']);

    expect($exception->getHeaders())->toHaveKey('Allow');
    expect($exception->getHeaders()['Allow'])->toBe('GET, POST');
    expect($exception->getAllowedMethods())->toBe(['GET', 'POST']);
});
