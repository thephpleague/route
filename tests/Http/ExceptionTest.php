<?php

namespace League\Route\Test\Http;

use League\Route\Http\Exception;

class ExceptionTest extends \PHPUnit_Framework_Testcase
{
    /**
     * Asserts that a HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception(400, 'Bad Request', null, ['header' => 'value']);
        } catch (Exception $e) {
            $this->assertSame(400, $e->getStatusCode());
            $this->assertSame('Bad Request', $e->getMessage());
            $this->assertArrayHasKey('header', $e->getHeaders());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":400,"message":"Bad Request"}',
                $response->getContent()
            );

            $this->assertSame(400, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Bad Request HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testBadRequestHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\BadRequestException;
        } catch (Exception $e) {
            $this->assertSame(400, $e->getStatusCode());
            $this->assertSame('Bad Request', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":400,"message":"Bad Request"}',
                $response->getContent()
            );

            $this->assertSame(400, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Conflict HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testConflictHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\ConflictException;
        } catch (Exception $e) {
            $this->assertSame(409, $e->getStatusCode());
            $this->assertSame('Conflict', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":409,"message":"Conflict"}',
                $response->getContent()
            );

            $this->assertSame(409, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Expectation Failed HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testExpectationFailedHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\ExpectationFailedException;
        } catch (Exception $e) {
            $this->assertSame(417, $e->getStatusCode());
            $this->assertSame('Expectation Failed', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":417,"message":"Expectation Failed"}',
                $response->getContent()
            );

            $this->assertSame(417, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Forbidden HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testForbiddenHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\ForbiddenException;
        } catch (Exception $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('Forbidden', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":403,"message":"Forbidden"}',
                $response->getContent()
            );

            $this->assertSame(403, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Gone HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testGoneHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\GoneException;
        } catch (Exception $e) {
            $this->assertSame(410, $e->getStatusCode());
            $this->assertSame('Gone', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":410,"message":"Gone"}',
                $response->getContent()
            );

            $this->assertSame(410, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a I'm a teapot HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testImATeapotHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\ImATeapotException;
        } catch (Exception $e) {
            $this->assertSame(418, $e->getStatusCode());
            $this->assertSame('I\'m a teapot', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":418,"message":"I\'m a teapot"}',
                $response->getContent()
            );

            $this->assertSame(418, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Length Required HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testLengthRequiredHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\LengthRequiredException;
        } catch (Exception $e) {
            $this->assertSame(411, $e->getStatusCode());
            $this->assertSame('Length Required', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":411,"message":"Length Required"}',
                $response->getContent()
            );

            $this->assertSame(411, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Method Not Allowed HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testMethodNotAllowedHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\MethodNotAllowedException(['GET', 'POST']);
        } catch (Exception $e) {
            $this->assertSame(405, $e->getStatusCode());
            $this->assertSame('Method Not Allowed', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":405,"message":"Method Not Allowed"}',
                $response->getContent()
            );

            $this->assertSame(405, $response->getStatusCode());
            $this->assertTrue($response->headers->has('Allow'));
            $this->assertSame('GET, POST', $response->headers->get('Allow'));
        }
    }

    /**
     * Asserts that a Not Acceptable HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testNotAcceptableHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\NotAcceptableException;
        } catch (Exception $e) {
            $this->assertSame(406, $e->getStatusCode());
            $this->assertSame('Not Acceptable', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":406,"message":"Not Acceptable"}',
                $response->getContent()
            );

            $this->assertSame(406, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Not Found HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testNotFoundHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\NotFoundException;
        } catch (Exception $e) {
            $this->assertSame(404, $e->getStatusCode());
            $this->assertSame('Not Found', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":404,"message":"Not Found"}',
                $response->getContent()
            );

            $this->assertSame(404, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Precondition Failed HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testPreconditionFailedHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\PreconditionFailedException;
        } catch (Exception $e) {
            $this->assertSame(412, $e->getStatusCode());
            $this->assertSame('Precondition Failed', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":412,"message":"Precondition Failed"}',
                $response->getContent()
            );

            $this->assertSame(412, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Precondition Required HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testPreconditionRequiredHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\PreconditionRequiredException;
        } catch (Exception $e) {
            $this->assertSame(428, $e->getStatusCode());
            $this->assertSame('Precondition Required', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":428,"message":"Precondition Required"}',
                $response->getContent()
            );

            $this->assertSame(428, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Too Many Requests HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testTooManyRequestsHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\TooManyRequestsException;
        } catch (Exception $e) {
            $this->assertSame(429, $e->getStatusCode());
            $this->assertSame('Too Many Requests', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":429,"message":"Too Many Requests"}',
                $response->getContent()
            );

            $this->assertSame(429, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Unauthorized HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testUnauthorizedHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\UnauthorizedException;
        } catch (Exception $e) {
            $this->assertSame(401, $e->getStatusCode());
            $this->assertSame('Unauthorized', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":401,"message":"Unauthorized"}',
                $response->getContent()
            );

            $this->assertSame(401, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Unprocessable Entity HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testUnprocessableEntityHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\UnprocessableEntityException;
        } catch (Exception $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertSame('Unprocessable Entity', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":422,"message":"Unprocessable Entity"}',
                $response->getContent()
            );

            $this->assertSame(422, $response->getStatusCode());
        }
    }

    /**
     * Asserts that a Unsupported Media HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testUnsupportedMediaHttpExceptionIsBuiltCorrectly()
    {
        try {
            throw new Exception\UnsupportedMediaException;
        } catch (Exception $e) {
            $this->assertSame(415, $e->getStatusCode());
            $this->assertSame('Unsupported Media', $e->getMessage());

            $response = $e->getJsonResponse();

            $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);

            $this->assertJsonStringEqualsJsonString(
                '{"status_code":415,"message":"Unsupported Media"}',
                $response->getContent()
            );

            $this->assertSame(415, $response->getStatusCode());
        }
    }
}
