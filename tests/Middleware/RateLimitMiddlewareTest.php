<?php

declare(strict_types=1);

namespace RateLimiter\Tests\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RateLimiter\Middleware\RateLimitMiddleware;
use RateLimiter\Storage\ArrayStorage;
use RateLimiter\TokenBucketLimiter;

final class RateLimitMiddlewareTest extends TestCase
{
    private function handlerReturning200(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $factory = new Psr17Factory();
                return $factory->createResponse(200);
            }
        };
    }

    public function testAllowsRequestWithinLimitAndAddsHeaders(): void
    {
        $limiter = new TokenBucketLimiter(new ArrayStorage(), capacity: 2, refillRate: 1.0);
        $factory = new Psr17Factory();
        $middleware = new RateLimitMiddleware($limiter, $factory);

        $request = new ServerRequest('GET', '/api/resource', serverParams: ['REMOTE_ADDR' => '1.2.3.4']);

        $response = $middleware->process($request, $this->handlerReturning200());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('2', $response->getHeaderLine('X-RateLimit-Limit'));
        $this->assertSame('1', $response->getHeaderLine('X-RateLimit-Remaining'));
    }

    public function testReturns429WhenLimitExceeded(): void
    {
        $limiter = new TokenBucketLimiter(new ArrayStorage(), capacity: 1, refillRate: 1.0);
        $factory = new Psr17Factory();
        $middleware = new RateLimitMiddleware($limiter, $factory);

        $request = new ServerRequest('GET', '/api/resource', serverParams: ['REMOTE_ADDR' => '1.2.3.4']);

        $middleware->process($request, $this->handlerReturning200()); // consumes the only token
        $response = $middleware->process($request, $this->handlerReturning200());

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('0', $response->getHeaderLine('X-RateLimit-Remaining'));
        $this->assertNotEmpty($response->getHeaderLine('Retry-After'));
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testDifferentIpsAreLimitedIndependently(): void
    {
        $limiter = new TokenBucketLimiter(new ArrayStorage(), capacity: 1, refillRate: 1.0);
        $factory = new Psr17Factory();
        $middleware = new RateLimitMiddleware($limiter, $factory);

        $requestA = new ServerRequest('GET', '/api/resource', serverParams: ['REMOTE_ADDR' => '1.1.1.1']);
        $requestB = new ServerRequest('GET', '/api/resource', serverParams: ['REMOTE_ADDR' => '2.2.2.2']);

        $responseA = $middleware->process($requestA, $this->handlerReturning200());
        $responseB = $middleware->process($requestB, $this->handlerReturning200());

        $this->assertSame(200, $responseA->getStatusCode());
        $this->assertSame(200, $responseB->getStatusCode());
    }

    public function testXForwardedForTakesPriorityOverRemoteAddr(): void
    {
        $limiter = new TokenBucketLimiter(new ArrayStorage(), capacity: 1, refillRate: 1.0);
        $factory = new Psr17Factory();
        $middleware = new RateLimitMiddleware($limiter, $factory);

        $request = (new ServerRequest('GET', '/api/resource', serverParams: ['REMOTE_ADDR' => '9.9.9.9']))
            ->withHeader('X-Forwarded-For', '5.5.5.5, 9.9.9.9');

        $response = $middleware->process($request, $this->handlerReturning200());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('0', $response->getHeaderLine('X-RateLimit-Remaining'));
    }
}