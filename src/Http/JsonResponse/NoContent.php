<?php

namespace League\Route\Http\JsonResponse;

use Symfony\Component\HttpFoundation\JsonResponse;

class NoContent extends JsonResponse
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $headers = [])
    {
        parent::__construct(null, 204, $headers);
    }
}
