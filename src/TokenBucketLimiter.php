<?php

declare(strict_types=1);

namespace RateLimiter;

use RateLimiter\Storage\StorageInterface;

/**
 * Token Bucket rate limiter.
 *
 * Allows bursts up to `$capacity` tokens, then refills gradually at
 * `$refillRate` tokens per second. Good default choice for APIs: smooths
 * traffic while still tolerating short bursts.
 */
final class TokenBucketLimiter implements RateLimiterInterface
{
    /**
     * @param StorageInterface $storage    Backend used to persist bucket state.
     * @param int               $capacity   Maximum tokens the bucket can hold (the burst limit).
     * @param float             $refillRate Tokens added per second.
     * @param int               $ttlSeconds How long an idle bucket's state is kept in storage.
     */
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly int $capacity,
        private readonly float $refillRate,
        private readonly int $ttlSeconds = 3600,
    ) {
        if ($this->capacity <= 0) {
            throw new \InvalidArgumentException('capacity must be greater than 0');
        }

        if ($this->refillRate <= 0) {
            throw new \InvalidArgumentException('refillRate must be greater than 0');
        }
    }

    public function attempt(string $key, int $cost = 1): RateLimitResult
    {
        if ($cost <= 0) {
            throw new \InvalidArgumentException('cost must be greater than 0');
        }

        $now = microtime(true);
        $state = $this->storage->get($key);

        if ($state === null) {
            $tokens = (float) $this->capacity;
            $lastRefillAt = $now;
        } else {
            /** @var float $tokens */
            $tokens = $state['tokens'];
            /** @var float $lastRefillAt */
            $lastRefillAt = $state['lastRefillAt'];
        }

        // Refill based on time elapsed since last check.
        $elapsed = max(0.0, $now - $lastRefillAt);
        $tokens = min($this->capacity, $tokens + $elapsed * $this->refillRate);

        if ($tokens >= $cost) {
            $tokens -= $cost;

            $this->storage->set($key, [
                'tokens' => $tokens,
                'lastRefillAt' => $now,
            ], $this->ttlSeconds);

            return RateLimitResult::allow(
                limit: $this->capacity,
                remaining: (int) floor($tokens),
            );
        }

        // Not enough tokens — persist the refill progress anyway so we
        // don't lose it, but don't deduct.
        $this->storage->set($key, [
            'tokens' => $tokens,
            'lastRefillAt' => $now,
        ], $this->ttlSeconds);

        $missing = $cost - $tokens;
        $retryAfter = (int) ceil($missing / $this->refillRate);

        return RateLimitResult::deny(
            limit: $this->capacity,
            retryAfterSeconds: max(1, $retryAfter),
        );
    }
}