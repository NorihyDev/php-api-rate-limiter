<?php

declare(strict_types=1);

namespace RateLimiter\Storage;

/**
 * In-memory storage backend.
 *
 * State only lives for the lifetime of the PHP process/request, so this
 * is mainly useful for tests, CLI tools, or single-worker apps. For
 * anything running across multiple processes/servers, use a shared
 * backend like RedisStorage instead.
 */
final class ArrayStorage implements StorageInterface
{
    /**
     * @var array<string, array{state: array<string, mixed>, expiresAt: int}>
     */
    private array $entries = [];

    public function get(string $key): ?array
    {
        $entry = $this->entries[$key] ?? null;

        if ($entry === null) {
            return null;
        }

        if ($entry['expiresAt'] <= time()) {
            unset($this->entries[$key]);
            return null;
        }

        return $entry['state'];
    }

    public function set(string $key, array $state, int $ttlSeconds): void
    {
        $this->entries[$key] = [
            'state' => $state,
            'expiresAt' => time() + $ttlSeconds,
        ];
    }

    public function delete(string $key): void
    {
        unset($this->entries[$key]);
    }
}