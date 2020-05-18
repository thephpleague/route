<?php

declare(strict_types=1);

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http;

class UnauthorizedException extends Http\Exception
{
    public function __construct(string $message = 'Unauthorized', ?Exception $previous = null, int $code = 0)
    {
        parent::__construct(401, $message, $previous, [], $code);
    }
}
