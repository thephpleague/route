<?php

declare(strict_types=1);

use League\Route\Http\Response\Decorator\DefaultHeaderDecorator;
use Psr\Http\Message\ResponseInterface;

test('default header decorator only adds headers that are not already present on the response', function () {
    $decorator = new DefaultHeaderDecorator([
        'content-type' => 'application/json',
        'custom-key' => 'custom value',
    ]);

    $response = Mockery::mock(ResponseInterface::class);

    $response
        ->shouldReceive('hasHeader')
        ->twice()
        ->andReturnUsing(fn(string $header) => $header !== 'content-type');

    $response
        ->shouldReceive('withAddedHeader')
        ->once()
        ->with('content-type', 'application/json')
        ->andReturnSelf();

    $result = $decorator($response);

    expect($result)->toBeInstanceOf(ResponseInterface::class);
});
