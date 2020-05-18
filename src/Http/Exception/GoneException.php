<?php

declare(strict_types=1);

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http;

class GoneException extends Http\Exception
{
    public function __construct(string $message = 'Gone', ?Exception $previous = null, int $code = 0)
    {
        parent::__construct(410, $message, $previous, [], $code);
    }
}
