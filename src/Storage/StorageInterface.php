<?php

declare(strict_types=1);

namespace RateLimiter\Storage;

/**
 * Contract for a rate limiter storage backend.
 *
 * Implementations hold per-key counters/timestamps used by the
 * limiting algorithms (Token Bucket, Sliding Window, etc.).
 */
interface StorageInterface
{
    /**
     * Get the raw state stored for a given key.
     *
     * @return array<string, mixed>|null Null if no state exists yet.
     */
    public function get(string $key): ?array;

    /**
     * Persist the state for a given key.
     *
     * @param array<string, mixed> $state
     * @param int $ttlSeconds Seconds after which the entry may be discarded.
     */
    public function set(string $key, array $state, int $ttlSeconds): void;

    /**
     * Remove all state for a given key (useful for tests / manual resets).
     */
    public function delete(string $key): void;
}