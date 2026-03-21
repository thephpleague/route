<?php

declare(strict_types=1);

namespace League\Route\Http\Exception;

use Exception;
use League\Route\Http;

class MethodNotAllowedException extends Http\Exception
{
    /** @param array<string> $allowedMethods */
    public function __construct(
        private readonly array $allowedMethods = [],
        string $message = 'Method Not Allowed',
        ?Exception $previous = null,
        int $code = 0,
    ) {
        parent::__construct(405, $message, $previous, ['Allow' => implode(', ', $allowedMethods)], $code);
    }

    /** @return array<string> */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
