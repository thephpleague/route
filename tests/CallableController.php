<?php
/**
 * League\Route\Test\CallableController
 */

namespace League\Route\Test;

/**
 * CallableController
 */
class CallableController
{
    /**
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param Symfony\Component\HttpFoundation\Response $response
     */
    public function __invoke(
        Symfony\Component\HttpFoundation\Request $request,
        Symfony\Component\HttpFoundation\Response $response
    ) {

    }
}
