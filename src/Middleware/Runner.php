<?php

namespace League\Route\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Runner
{
    /**
     * @var callable[]
     */
    protected $before = [];

    /**
     * @var callable[]
     */
    protected $after = [];

    /**
     * @var callable[]
     */
    protected $queue = [];

    /**
     * Whether the queue has been built.
     *
     * @var boolean
     */
    protected $queued = false;

    /**
     * Build and invoke a stack of middleware around a specific middleware.
     *
     * @param callable                                 $middleware
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function run(callable $middleware, ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->queued === false) {
            $this->buildQueue($middleware);
        }

        return $this->next($request, $response);
    }

    /**
     * Next callable to be passed throughout queue.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface      $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function next(ServerRequestInterface $request, ResponseInterface $response)
    {
        if (empty($this->queue)) {
            return $response;
        }

        $middleware = array_shift($this->queue);
        return $middleware($request, $response, [$this, 'next']);
    }

    /**
     * Add a middleware to the before queue.
     *
     * @param callable $middleware
     *
     * @return self
     */
    public function before(callable $middleware)
    {
        $this->before[] = $middleware;

        return $this;
    }

    /**
     * Add a middleware to the after queue.
     *
     * @param callable $middleware
     *
     * @return self
     */
    public function after(callable $middleware)
    {
        $this->after[] = $middleware;

        return $this;
    }

    /**
     * Build the queue of middleware.
     *
     * @param callable $middleware
     *
     * @return void
     */
    protected function buildQueue(callable $middleware)
    {
        $this->queue   = $this->before;
        $this->queue[] = $middleware;
        $this->queue   = array_merge($this->queue, $this->after);

        $this->queued = true;
    }
}
