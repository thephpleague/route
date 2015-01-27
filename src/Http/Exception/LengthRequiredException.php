<?php

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http\Exception as HttpException;

class LengthRequiredException extends HttpException
{
    /**
     * Constructor
     *
     * @param string     $message
     * @param \Exception $previous
     * @param integer    $code
     */
    public function __construct($message = 'Length Required', Exception $previous = null, $code = 0)
    {
        parent::__construct(411, $message, $previous, [], $code);
    }
}
