<?php

declare(strict_types=1);

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http;

class UnprocessableEntityException extends Http\Exception
{
    public function __construct(string $message = 'Unprocessable Entity', ?Exception $previous = null, int $code = 0)
    {
        parent::__construct(422, $message, $previous, [], $code);
    }
}
