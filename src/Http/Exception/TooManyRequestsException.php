<?php

declare(strict_types=1);

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http;

class TooManyRequestsException extends Http\Exception
{
    public function __construct(string $message = 'Too Many Requests', ?Exception $previous = null, int $code = 0)
    {
        parent::__construct(429, $message, $previous, [], $code);
    }
}
