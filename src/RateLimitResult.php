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
        public readonly ?int $resetAt = null,
    ) {
    }

    public static function allow(int $limit, int $remaining, ?int $resetAt = null): self
    {
        return new self(allowed: true, limit: $limit, remaining: max(0, $remaining), resetAt: $resetAt);
    }

    public static function deny(int $limit, int $retryAfterSeconds, int $remaining = 0, ?int $resetAt = null): self
    {
        return new self(allowed: false, limit: $limit, remaining: max(0, $remaining), retryAfterSeconds: $retryAfterSeconds, resetAt: $resetAt);
    }
}
