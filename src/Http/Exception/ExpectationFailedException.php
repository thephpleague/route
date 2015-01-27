<?php

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http\Exception as HttpException;

class ExpectationFailedException extends HttpException
{
    /**
     * Constructor
     *
     * @param string     $message
     * @param \Exception $previous
     * @param integer    $code
     */
    public function __construct($message = 'Expectation Failed', Exception $previous = null, $code = 0)
    {
        parent::__construct(417, $message, $previous, [], $code);
    }
}
