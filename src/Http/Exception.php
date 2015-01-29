<?php

namespace League\Route\Http;

use League\Route\Http\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class Exception extends \Exception implements HttpExceptionInterface
{
    /**
     * @var integer
     */
    protected $status;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * Constructor
     *
     * @param integer    $status
     * @param string     $message
     * @param \Exception $previous
     * @param array      $headers
     * @param integer    $code
     */
    public function __construct(
        $status,
        $message             = null,
        \Exception $previous = null,
        array $headers       = [],
        $code                = 0
    ) {
        $this->status  = $status;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getJsonResponse()
    {
        $body = [
            'status_code' => $this->getStatusCode(),
            'message'     => $this->getMessage()
        ];

        return new JsonResponse($body, $this->getStatusCode(), $this->getHeaders());
    }
}
