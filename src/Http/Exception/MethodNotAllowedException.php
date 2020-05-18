<?php

declare(strict_types=1);

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http;

class MethodNotAllowedException extends Http\Exception
{
    public function __construct(
        array $allowed = [],
        string $message = 'Method Not Allowed',
        ?Exception $previous = null,
        int $code = 0
    ) {
        $headers = [
            'Allow' => implode(', ', $allowed)
        ];

        parent::__construct(405, $message, $previous, $headers, $code);
    }
}
