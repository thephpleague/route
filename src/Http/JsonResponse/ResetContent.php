<?php

namespace League\Route\Http\JsonResponse;

use Symfony\Component\HttpFoundation\JsonResponse;

class ResetContent extends JsonResponse
{
    /**
     * {@inheritdoc}
     */
    public function __construct($data = null, array $headers = [])
    {
        parent::__construct($data, 205, $headers);
    }
}
