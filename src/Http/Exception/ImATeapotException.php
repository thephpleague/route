<?php

declare(strict_types=1);

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http;

class ImATeapotException extends Http\Exception
{
    public function __construct(string $message = "I'm a teapot", ?Exception $previous = null, int $code = 0)
    {
        parent::__construct(418, $message, $previous, [], $code);
    }
}
