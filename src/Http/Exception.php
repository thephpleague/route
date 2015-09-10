<?php

namespace League\Route\Http;

use League\Route\Http\Exception\HttpExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class Exception extends \Exception implements HttpExceptionInterface
{
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var string
     */
    protected $message;

    /**
     * @var integer
     */
    protected $status;

    /**
     * Constructor.
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
        $this->headers = $headers;
        $this->message = $message;
        $this->status  = $status;

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
    public function buildJsonResponse(ResponseInterface $response)
    {
        $this->headers['content-type'] = 'application/json';

        foreach ($this->headers as $key => $value) {
            $response = $response->withAddedHeader($key, $value);
        }

        if ($response->getBody()->isWritable()) {
            $response->getBody()->write(json_encode([
                'status_code'   => $this->status,
                'reason_phrase' => $this->message
            ]));
        }

        return $response->withStatus($this->status, $this->message);
    }
}
