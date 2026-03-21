<?php

declare(strict_types=1);

use League\Route\Http\Exception;
use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\ConflictException;
use League\Route\Http\Exception\ExpectationFailedException;
use League\Route\Http\Exception\ForbiddenException;
use League\Route\Http\Exception\GoneException;
use League\Route\Http\Exception\ImATeapotException;
use League\Route\Http\Exception\LengthRequiredException;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotAcceptableException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Http\Exception\PreconditionFailedException;
use League\Route\Http\Exception\PreconditionRequiredException;
use League\Route\Http\Exception\TooManyRequestsException;
use League\Route\Http\Exception\UnauthorizedException;
use League\Route\Http\Exception\UnavailableForLegalReasonsException;
use League\Route\Http\Exception\UnprocessableEntityException;
use League\Route\Http\Exception\UnsupportedMediaException;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

function assertExceptionBuildsJsonResponse(Exception $exception): void
{
    $json = json_encode([
        'status_code' => $exception->getStatusCode(),
        'reason_phrase' => $exception->getMessage(),
    ]);

    /** @var StreamInterface&MockInterface $body */
    $body = Mockery::mock(StreamInterface::class);
    $body->shouldReceive('isWritable')->once()->andReturn(true);
    $body->shouldReceive('write')->once()->with($json);

    /** @var ResponseInterface&MockInterface $response */
    $response = Mockery::mock(ResponseInterface::class);
    $response->allows('withAddedHeader')->andReturnSelf();
    $response->shouldReceive('getBody')->twice()->andReturn($body);
    $response->shouldReceive('withStatus')->once()->with($exception->getStatusCode(), $exception->getMessage())->andReturnSelf();

    $result = $exception->buildJsonResponse($response);

    expect($result)->toBeInstanceOf(ResponseInterface::class);
}

test('base http exception is built correctly with status, message, headers, and json response', function () {
    $exception = new Exception(400, 'Bad Request', null, ['header' => 'value']);

    expect($exception->getStatusCode())->toBe(400);
    expect($exception->getMessage())->toBe('Bad Request');
    expect($exception->getHeaders())->toHaveKey('header');

    assertExceptionBuildsJsonResponse($exception);
});

test('http exception subclass is built correctly with expected status code, reason phrase, and json response', function (string $exceptionClass, int $statusCode, string $reasonPhrase) {
    $exception = new $exceptionClass();

    expect($exception->getStatusCode())->toBe($statusCode);
    expect($exception->getMessage())->toBe($reasonPhrase);

    assertExceptionBuildsJsonResponse($exception);
})->with([
    'BadRequest' => [BadRequestException::class, 400, 'Bad Request'],
    'Conflict' => [ConflictException::class, 409, 'Conflict'],
    'ExpectationFailed' => [ExpectationFailedException::class, 417, 'Expectation Failed'],
    'Forbidden' => [ForbiddenException::class, 403, 'Forbidden'],
    'Gone' => [GoneException::class, 410, 'Gone'],
    'ImATeapot' => [ImATeapotException::class, 418, "I'm a teapot"],
    'LengthRequired' => [LengthRequiredException::class, 411, 'Length Required'],
    'NotAcceptable' => [NotAcceptableException::class, 406, 'Not Acceptable'],
    'NotFound' => [NotFoundException::class, 404, 'Not Found'],
    'PreconditionFailed' => [PreconditionFailedException::class, 412, 'Precondition Failed'],
    'PreconditionRequired' => [PreconditionRequiredException::class, 428, 'Precondition Required'],
    'TooManyRequests' => [TooManyRequestsException::class, 429, 'Too Many Requests'],
    'Unauthorized' => [UnauthorizedException::class, 401, 'Unauthorized'],
    'UnavailableForLegalReasons' => [UnavailableForLegalReasonsException::class, 451, 'Unavailable For Legal Reasons'],
    'UnprocessableEntity' => [UnprocessableEntityException::class, 422, 'Unprocessable Entity'],
    'UnsupportedMedia' => [UnsupportedMediaException::class, 415, 'Unsupported Media'],
    'MethodNotAllowed' => [MethodNotAllowedException::class, 405, 'Method Not Allowed'],
]);
