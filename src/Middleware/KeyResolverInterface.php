<?php

declare(strict_types=1);

namespace RateLimiter\Middleware;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Extracts the rate-limit key (e.g. IP address, API key, user ID)
 * from an incoming request.
 */
interface KeyResolverInterface
{
    public function resolve(ServerRequestInterface $request): string;
}