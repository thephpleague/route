<?php declare(strict_types=1);

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
     * @param int        $status
     * @param string     $message
     * @param \Exception $previous
     * @param array      $headers
     * @param int        $code
     */
    public function __construct(
        int        $status,
        string     $message = null,
        \Exception $previous = null,
        array      $headers = [],
        int        $code = 0
    ) {
        $this->headers = $headers;
        $this->message = $message;
        $this->status  = $status;

        parent::__construct($message, $code, $previous);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function buildJsonResponse(ResponseInterface $response): ResponseInterface
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
