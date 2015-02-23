<?php

namespace League\Route\Test\Http\JsonResponse;

use League\Route\Http\JsonResponse\NoContent;

class NoContentTest extends \PHPUnit_Framework_Testcase
{
    /**
     * Asserts that new NoContent responses have a 204 status code
     *
     * @return void
     */
    public function testConstructor()
    {
        $response = new NoContent();
        $this->assertSame(204, $response->getStatusCode());
    }
}
