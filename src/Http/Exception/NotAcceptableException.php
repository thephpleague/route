<?php

declare(strict_types=1);

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http;

class NotAcceptableException extends Http\Exception
{
    public function __construct(string $message = 'Not Acceptable', ?Exception $previous = null, int $code = 0)
    {
        parent::__construct(406, $message, $previous, [], $code);
    }
}
