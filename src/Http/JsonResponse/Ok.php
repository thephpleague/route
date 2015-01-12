<?php

namespace League\Route\Http\JsonResponse;

use Symfony\Component\HttpFoundation\JsonResponse;

class Ok extends JsonResponse
{
    /**
     * {@inheritdoc}
     */
    public function __construct($data = null, array $headers = [])
    {
        parent::__construct($data, 200, $headers);
    }
}
