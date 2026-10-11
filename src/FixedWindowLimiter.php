<?php

declare(strict_types=1);

namespace RateLimiter;

use RateLimiter\Storage\StorageInterface;

/** A simple counter that resets at fixed, wall-clock-aligned intervals. */
final class FixedWindowLimiter implements RateLimiterInterface
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly int $limit,
        private readonly int $windowSeconds,
    ) {
        if ($limit <= 0 || $windowSeconds <= 0) {
            throw new \InvalidArgumentException('limit and windowSeconds must be greater than 0');
        }
    }

    public function attempt(string $key, int $cost = 1): RateLimitResult
    {
        if ($cost <= 0) {
            throw new \InvalidArgumentException('cost must be greater than 0');
        }

        $now = time();
        $window = intdiv($now, $this->windowSeconds);
        $state = $this->storage->get($key);
        $count = ($state['window'] ?? null) === $window ? (int) $state['count'] : 0;
        $resetAt = ($window + 1) * $this->windowSeconds;

        if ($count + $cost > $this->limit) {
            return RateLimitResult::deny($this->limit, max(1, $resetAt - $now), $this->limit - $count);
        }

        $count += $cost;
        $this->storage->set($key, ['window' => $window, 'count' => $count], max(1, $resetAt - $now));

        return RateLimitResult::allow($this->limit, $this->limit - $count, $resetAt);
    }
}
