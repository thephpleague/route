<?php

declare(strict_types=1);

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http;

class UnavailableForLegalReasonsException extends Http\Exception
{
    public function __construct(
        string $message = 'Unavailable For Legal Reasons',
        ?Exception $previous = null,
        int $code = 0
    ) {
        parent::__construct(451, $message, $previous, [], $code);
    }
}
