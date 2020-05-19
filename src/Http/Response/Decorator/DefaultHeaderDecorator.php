<?php

declare(strict_types=1);

namespace League\Route\Http\Response\Decorator;

use Psr\Http\Message\ResponseInterface;

class DefaultHeaderDecorator
{
    /**
     * @var array
     */
    protected $headers = [];

    public function __construct(array $headers = [])
    {
        $this->addDefaultHeaders($headers);
    }

    public function __invoke(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->headers as $name => $value) {
            if (false === $response->hasHeader($name)) {
                $response = $response->withAddedHeader($name, $value);
            }
        }

        return $response;
    }

    public function addDefaultHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function addDefaultHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->addDefaultHeader($name, $value);
        }

        return $this;
    }
}
