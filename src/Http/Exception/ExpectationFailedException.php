<?php

declare(strict_types=1);

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http;

class ExpectationFailedException extends Http\Exception
{
    public function __construct(string $message = 'Expectation Failed', ?Exception $previous = null, int $code = 0)
    {
        parent::__construct(417, $message, $previous, [], $code);
    }
}
