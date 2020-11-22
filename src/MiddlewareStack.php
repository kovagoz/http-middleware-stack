<?php

namespace Kovagoz\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareStack implements RequestHandlerInterface
{
    private ResponseInterface $defaultResponse;
    private array             $middlewares = [];

    public function __construct(ResponseInterface $defaultResponse)
    {
        $this->defaultResponse = $defaultResponse;
    }

    /**
     * Place a new middleware on top of the stack.
     *
     * @param \Psr\Http\Server\MiddlewareInterface $middleware
     */
    public function push(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($this->middlewares)) {
            return $this->defaultResponse;
        }

        // Create a new stack...
        $stack = new self($this->defaultResponse);

        // ...from the remaining middlewares
        foreach (array_slice($this->middlewares, 0, -1) as $middleware) {
            $stack->push($middleware);
        }

        return end($this->middlewares)->process($request, $stack);
    }
}
