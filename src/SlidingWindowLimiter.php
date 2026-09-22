<?php

declare(strict_types=1);

namespace RateLimiter;

use RateLimiter\Storage\StorageInterface;

/**
 * Sliding Window Log rate limiter.
 *
 * Tracks exact request timestamps per key and only counts the ones that
 * fall within the last `$windowSeconds`. Stricter than Token Bucket: no
 * burst allowance beyond the configured limit within the window.
 */
final class SlidingWindowLimiter implements RateLimiterInterface
{
    /**
     * @param StorageInterface $storage       Backend used to persist request logs.
     * @param int               $limit         Max requests allowed per window.
     * @param int               $windowSeconds Length of the sliding window, in seconds.
     */
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly int $limit,
        private readonly int $windowSeconds,
    ) {
        if ($this->limit <= 0) {
            throw new \InvalidArgumentException('limit must be greater than 0');
        }

        if ($this->windowSeconds <= 0) {
            throw new \InvalidArgumentException('windowSeconds must be greater than 0');
        }
    }

    public function attempt(string $key, int $cost = 1): RateLimitResult
    {
        if ($cost <= 0) {
            throw new \InvalidArgumentException('cost must be greater than 0');
        }

        $now = microtime(true);
        $windowStart = $now - $this->windowSeconds;

        $state = $this->storage->get($key);
        /** @var float[] $timestamps */
        $timestamps = $state['timestamps'] ?? [];

        // Drop entries that have aged out of the window.
        $timestamps = array_values(array_filter(
            $timestamps,
            static fn (float $ts): bool => $ts > $windowStart,
        ));

        $currentCount = count($timestamps);

        if ($currentCount + $cost > $this->limit) {
            // Persist the pruned log even on denial, so old entries don't linger.
            $this->storage->set($key, ['timestamps' => $timestamps], $this->windowSeconds);

            $retryAfter = $this->retryAfterSeconds($timestamps, $now);

            return RateLimitResult::deny(
                limit: $this->limit,
                retryAfterSeconds: $retryAfter,
            );
        }

        for ($i = 0; $i < $cost; $i++) {
            $timestamps[] = $now;
        }

        $this->storage->set($key, ['timestamps' => $timestamps], $this->windowSeconds);

        return RateLimitResult::allow(
            limit: $this->limit,
            remaining: $this->limit - count($timestamps),
        );
    }

    /**
     * Seconds until the oldest timestamp falls out of the window, freeing
     * up at least one slot.
     *
     * @param float[] $timestamps
     */
    private function retryAfterSeconds(array $timestamps, float $now): int
    {
        if ($timestamps === []) {
            return 1;
        }

        $oldest = min($timestamps);
        $freesAt = $oldest + $this->windowSeconds;

        return max(1, (int) ceil($freesAt - $now));
    }
}