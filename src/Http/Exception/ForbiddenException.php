<?php

declare(strict_types=1);

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http;

class ForbiddenException extends Http\Exception
{
    public function __construct(string $message = 'Forbidden', ?Exception $previous = null, int $code = 0)
    {
        parent::__construct(403, $message, $previous, [], $code);
    }
}
