<?php

declare(strict_types=1);

namespace RateLimiter;

/**
 * Result of a rate limit check, returned by RateLimiterInterface::attempt().
 */
final class RateLimitResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly int $limit,
        public readonly int $remaining,
        public readonly int $retryAfterSeconds = 0,
    ) {
    }

    public static function allow(int $limit, int $remaining): self
    {
        return new self(allowed: true, limit: $limit, remaining: max(0, $remaining));
    }

    public static function deny(int $limit, int $retryAfterSeconds): self
    {
        return new self(allowed: false, limit: $limit, remaining: 0, retryAfterSeconds: $retryAfterSeconds);
    }
}