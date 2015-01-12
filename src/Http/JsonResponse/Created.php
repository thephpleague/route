<?php

namespace League\Route\Http\JsonResponse;

use Symfony\Component\HttpFoundation\JsonResponse;

class Created extends JsonResponse
{
    /**
     * {@inheritdoc}
     */
    public function __construct($data = null, array $headers = [])
    {
        parent::__construct($data, 201, $headers);
    }
}
