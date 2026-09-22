<?php

declare(strict_types=1);

namespace RateLimiter\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RateLimiter\RateLimiterInterface;

/**
 * PSR-15 middleware that enforces a RateLimiterInterface on every request.
 *
 * On success, forwards the request and stamps the response with
 * X-RateLimit-Limit / X-RateLimit-Remaining. On denial, short-circuits
 * with a 429 response including Retry-After and X-RateLimit-* headers.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RateLimiterInterface $limiter,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly KeyResolverInterface $keyResolver = new IpKeyResolver(),
        private readonly int $cost = 1,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key = $this->keyResolver->resolve($request);
        $result = $this->limiter->attempt($key, $this->cost);

        if (!$result->allowed) {
            $response = $this->responseFactory
                ->createResponse(429, 'Too Many Requests')
                ->withHeader('Retry-After', (string) $result->retryAfterSeconds)
                ->withHeader('X-RateLimit-Limit', (string) $result->limit)
                ->withHeader('X-RateLimit-Remaining', '0');

            $response->getBody()->write(json_encode([
                'error' => 'Too Many Requests',
                'retry_after_seconds' => $result->retryAfterSeconds,
            ], JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/json');
        }

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $result->limit)
            ->withHeader('X-RateLimit-Remaining', (string) $result->remaining);
    }
}