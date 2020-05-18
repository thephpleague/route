<?php

declare(strict_types=1);

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http;

class LengthRequiredException extends Http\Exception
{
    public function __construct(string $message = 'Length Required', ?Exception $previous = null, int $code = 0)
    {
        parent::__construct(411, $message, $previous, [], $code);
    }
}
