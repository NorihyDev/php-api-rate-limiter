<?php

declare(strict_types=1);

namespace RateLimiter\Storage;

/**
 * Redis-backed storage.
 *
 * Shares rate limit state across multiple processes/servers — required
 * for any real multi-worker or multi-node deployment. Requires the
 * phpredis extension (ext-redis).
 *
 * State is stored as a JSON string under a prefixed key, with Redis'
 * native TTL (EXPIRE) handling expiry — no manual pruning needed.
 */
final class RedisStorage implements StorageInterface
{
    public function __construct(
        private readonly \Redis $redis,
        private readonly string $prefix = 'rate_limiter:',
    ) {
    }

    public function get(string $key): ?array
    {
        $raw = $this->redis->get($this->prefixedKey($key));

        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, associative: true);

        return is_array($decoded) ? $decoded : null;
    }

    public function set(string $key, array $state, int $ttlSeconds): void
    {
        $payload = json_encode($state, JSON_THROW_ON_ERROR);

        $this->redis->setex($this->prefixedKey($key), max(1, $ttlSeconds), $payload);
    }

    public function delete(string $key): void
    {
        $this->redis->del($this->prefixedKey($key));
    }

    private function prefixedKey(string $key): string
    {
        return $this->prefix . $key;
    }
}