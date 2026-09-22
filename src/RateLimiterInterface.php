<?php

declare(strict_types=1);

namespace RateLimiter;

/**
 * Contract implemented by every rate limiting algorithm
 * (Token Bucket, Sliding Window, Fixed Window, ...).
 */
interface RateLimiterInterface
{
    /**
     * Attempt to consume `$cost` units of quota for the given key.
     *
     * @param string $key  Unique identifier for the client/route being limited
     *                     (e.g. "user:42" or "ip:1.2.3.4").
     * @param int    $cost Number of units this request consumes (default 1).
     */
    public function attempt(string $key, int $cost = 1): RateLimitResult;
}