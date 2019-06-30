<?php declare(strict_types=1);

namespace League\Route\Http;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, StreamInterface};

class ExceptionTest extends TestCase
{
    /**
     * Abstraction test for building of response.
     *
     * @param Exception $e
     *
     * @return void
     */
    protected function responseTester(Exception $e): void
    {
        $json = json_encode([
            'status_code'   => $e->getStatusCode(),
            'reason_phrase' => $e->getMessage()
        ]);

        $body = $this->createMock(StreamInterface::class);

        $body
            ->expects($this->once())
            ->method('isWritable')
            ->willReturn(true)
        ;

        $body
            ->expects($this->once())
            ->method('write')
            ->with($json)
        ;

        $response = $this->createMock(ResponseInterface::class);

        $response
            ->expects($this->any())
            ->method('withAddedHeader')
            ->will($this->returnSelf())
        ;

        $response
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($body)
        ;

        $response
            ->expects($this->once())
            ->method('withStatus')
            ->with($e->getStatusCode(), $e->getMessage())
            ->will($this->returnSelf())
        ;

        $response = $e->buildJsonResponse($response);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
    /**
     * Asserts that a HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception(400, 'Bad Request', null, ['header' => 'value']);
        } catch (Exception $e) {
            $this->assertSame(400, $e->getStatusCode());
            $this->assertSame('Bad Request', $e->getMessage());
            $this->assertArrayHasKey('header', $e->getHeaders());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Bad Request HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testBadRequestHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\BadRequestException;
        } catch (Exception $e) {
            $this->assertSame(400, $e->getStatusCode());
            $this->assertSame('Bad Request', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Conflict HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testConflictHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\ConflictException;
        } catch (Exception $e) {
            $this->assertSame(409, $e->getStatusCode());
            $this->assertSame('Conflict', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Expectation Failed HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testExpectationFailedHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\ExpectationFailedException;
        } catch (Exception $e) {
            $this->assertSame(417, $e->getStatusCode());
            $this->assertSame('Expectation Failed', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Forbidden HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testForbiddenHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\ForbiddenException;
        } catch (Exception $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('Forbidden', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Gone HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testGoneHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\GoneException;
        } catch (Exception $e) {
            $this->assertSame(410, $e->getStatusCode());
            $this->assertSame('Gone', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a I'm a teapot HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testImATeapotHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\ImATeapotException;
        } catch (Exception $e) {
            $this->assertSame(418, $e->getStatusCode());
            $this->assertSame('I\'m a teapot', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Length Required HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testLengthRequiredHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\LengthRequiredException;
        } catch (Exception $e) {
            $this->assertSame(411, $e->getStatusCode());
            $this->assertSame('Length Required', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Method Not Allowed HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testMethodNotAllowedHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\MethodNotAllowedException(['GET', 'POST']);
        } catch (Exception $e) {
            $this->assertSame(405, $e->getStatusCode());
            $this->assertSame('Method Not Allowed', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Not Acceptable HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testNotAcceptableHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\NotAcceptableException;
        } catch (Exception $e) {
            $this->assertSame(406, $e->getStatusCode());
            $this->assertSame('Not Acceptable', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Not Found HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testNotFoundHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\NotFoundException;
        } catch (Exception $e) {
            $this->assertSame(404, $e->getStatusCode());
            $this->assertSame('Not Found', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Precondition Failed HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testPreconditionFailedHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\PreconditionFailedException;
        } catch (Exception $e) {
            $this->assertSame(412, $e->getStatusCode());
            $this->assertSame('Precondition Failed', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Precondition Required HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testPreconditionRequiredHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\PreconditionRequiredException;
        } catch (Exception $e) {
            $this->assertSame(428, $e->getStatusCode());
            $this->assertSame('Precondition Required', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Too Many Requests HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testTooManyRequestsHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\TooManyRequestsException;
        } catch (Exception $e) {
            $this->assertSame(429, $e->getStatusCode());
            $this->assertSame('Too Many Requests', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Unauthorized HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testUnauthorizedHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\UnauthorizedException;
        } catch (Exception $e) {
            $this->assertSame(401, $e->getStatusCode());
            $this->assertSame('Unauthorized', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Unprocessable Entity HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testUnprocessableEntityHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\UnprocessableEntityException;
        } catch (Exception $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertSame('Unprocessable Entity', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Unsupported Media HTTP Exception is built correctly when thrown
     *
     * @return void
     */
    public function testUnsupportedMediaHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\UnsupportedMediaException;
        } catch (Exception $e) {
            $this->assertSame(415, $e->getStatusCode());
            $this->assertSame('Unsupported Media', $e->getMessage());

            $this->responseTester($e);
        }
    }

    /**
     * Asserts that a Unavaliable For Legal Reasons HTTP Exception is built correctly when thrown.
     *
     * @return void
     */
    public function testUnavailableForLegalReasonsHttpExceptionIsBuiltCorrectly(): void
    {
        try {
            throw new Exception\UnavailableForLegalReasonsException;
        } catch (Exception $e) {
            $this->assertSame(451, $e->getStatusCode());
            $this->assertSame('Unavailable For Legal Reasons', $e->getMessage());

            $this->responseTester($e);
        }
    }
}
