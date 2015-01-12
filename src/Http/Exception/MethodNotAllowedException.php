<?php

namespace League\Route\Http\Exception;

use League\Route\Http\Exception as HttpException;

class MethodNotAllowedException extends HttpException
{
    /**
     * Constructor
     *
     * @param string     $message
     * @param \Exception $previous
     * @param integer    $code
     */
    public function __construct(array $allowed = [], $message = 'Method Not Allowed', \Exception $previous = null, $code = 0)
    {
        $headers = [
            'Allow' => implode(', ', $allowed)
        ];

        parent::__construct(405, $message, $previous, $headers, $code);
    }
}
