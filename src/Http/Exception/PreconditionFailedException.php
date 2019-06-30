<?php declare(strict_types=1);

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http\Exception as HttpException;

class PreconditionFailedException extends HttpException
{
    /**
     * Constructor
     *
     * @param string    $message
     * @param Exception $previous
     * @param int $code
     */
    public function __construct(string $message = 'Precondition Failed', ?Exception $previous = null, int $code = 0)
    {
        parent::__construct(412, $message, $previous, [], $code);
    }
}
