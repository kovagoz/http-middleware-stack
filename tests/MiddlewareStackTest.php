<?php

use Kovagoz\Http\MiddlewareStack;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareStackTest extends \PHPUnit\Framework\TestCase
{
    public function testReturnsDefaultResponseIfEmpty(): void
    {
        $request         = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $defaultResponse = $this->getMockForAbstractClass(ResponseInterface::class);

        // Create an empty stack
        $stack    = new MiddlewareStack($defaultResponse);
        $response = $stack->handle($request);

        self::assertSame($defaultResponse, $response);
    }

    /**
     * Testing the stack like behaviour.
     * @see https://en.wikipedia.org/wiki/Stack_(abstract_data_type)
     */
    public function testLastPushedMiddlewareResponsesFirst(): void
    {
        $request         = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $defaultResponse = $this->getMockForAbstractClass(ResponseInterface::class);

        // This will be the inner middleware
        $middleware1 = $this->getMockForAbstractClass(MiddlewareInterface::class);
        $middleware1->expects(self::never())->method('process');

        // This will be the outer middleware
        $middlewareResponse2 = $this->getMockForAbstractClass(ResponseInterface::class);
        $middleware2 = $this->getMockForAbstractClass(MiddlewareInterface::class);
        $middleware2
            ->expects(self::once())
            ->method('process')
            ->willReturn($middlewareResponse2);

        $stack = new MiddlewareStack($defaultResponse);
        $stack->push($middleware1);
        $stack->push($middleware2);

        $response = $stack->handle($request);

        // The outer middleware responses
        self::assertSame($middlewareResponse2, $response);
    }

    public function testPassRequestToTheNextMiddleware(): void
    {
        $request         = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $defaultResponse = $this->getMockForAbstractClass(ResponseInterface::class);

        // This will be the inner middleware
        $middlewareResponse1 = $this->getMockForAbstractClass(ResponseInterface::class);
        $middleware1 = $this->getMockForAbstractClass(MiddlewareInterface::class);
        $middleware1->expects(self::once())->method('process')->willReturn($middlewareResponse1);

        // This will be the outer middleware
        $middleware2 = $this->getMockForAbstractClass(MiddlewareInterface::class);
        $middleware2
            ->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                    // Pass request to the next middleware in the stack
                    return $handler->handle($request);
                }
            );

        $stack = new MiddlewareStack($defaultResponse);
        $stack->push($middleware1);
        $stack->push($middleware2);

        $response = $stack->handle($request);

        // The inner middleware responses
        self::assertSame($middlewareResponse1, $response);
    }

    /**
     * In this case all of the middlewares try to call the next one in the stack,
     * so at the end the default response should be returned.
     */
    public function testReturnsDefaultResponseIfNobodyRespond(): void
    {
        $request         = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $defaultResponse = $this->getMockForAbstractClass(ResponseInterface::class);

        // This will be the inner middleware
        $middleware1 = $this->getMockForAbstractClass(MiddlewareInterface::class);
        $middleware1
            ->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                    return $handler->handle($request);
                }
            );

        // This will be the outer middleware
        $middleware2 = $this->getMockForAbstractClass(MiddlewareInterface::class);
        $middleware2
            ->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                    return $handler->handle($request);
                }
            );

        $stack = new MiddlewareStack($defaultResponse);
        $stack->push($middleware1);
        $stack->push($middleware2);

        $response = $stack->handle($request);

        // None of the middlewares responded so stack returns
        // with the default response.
        self::assertSame($defaultResponse, $response);
    }
}
