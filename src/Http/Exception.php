<?php

declare(strict_types=1);

namespace League\Route\Http;

use League\Route\Http\Exception\HttpExceptionInterface;
use Override;
use Psr\Http\Message\ResponseInterface;

class Exception extends \Exception implements HttpExceptionInterface
{
    /**
     * @param string $message
     * @param array<string, string> $headers
     */
    public function __construct(
        protected int $status,
        protected $message = '',
        ?\Exception $previous = null,
        protected array $headers = [],
        int $code = 0,
    ) {
        parent::__construct($this->message, $code, $previous);
    }

    #[Override]
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /** @return array<string, string> */
    #[Override]
    public function getHeaders(): array
    {
        return $this->headers;
    }

    #[Override]
    public function buildJsonResponse(ResponseInterface $response): ResponseInterface
    {
        $this->headers['content-type'] = 'application/json';

        foreach ($this->headers as $key => $value) {
            /** @var ResponseInterface $response */
            $response = $response->withAddedHeader($key, $value);
        }

        if ($response->getBody()->isWritable()) {
            $body = json_encode([
                'status_code' => $this->status,
                'reason_phrase' => $this->message,
            ]);
            if (is_string($body)) {
                $response->getBody()->write($body);
            }
        }

        return $response->withStatus($this->status, $this->message);
    }
}
