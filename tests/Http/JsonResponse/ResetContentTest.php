<?php

namespace League\Route\Test\Http\JsonResponse;

use League\Route\Http\JsonResponse\ResetContent;

class ResetContentTest extends \PHPUnit_Framework_Testcase
{
    /**
     * Asserts that new ResetContent responses have a 205 status code
     *
     * @return void
     */
    public function testConstructor()
    {
        $response = new ResetContent();
        $this->assertSame(205, $response->getStatusCode());
    }
}
