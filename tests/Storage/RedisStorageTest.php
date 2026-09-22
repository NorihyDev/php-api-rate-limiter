<?php

declare(strict_types=1);

namespace RateLimiter\Tests\Storage;

use PHPUnit\Framework\TestCase;
use RateLimiter\Storage\RedisStorage;

final class RedisStorageTest extends TestCase
{
    private ?\Redis $redis = null;

    protected function setUp(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('ext-redis is not installed.');
        }

        $this->redis = new \Redis();

        try {
            $connected = $this->redis->connect('127.0.0.1', 6379, 1.0);
        } catch (\RedisException) {
            $connected = false;
        }

        if (!$connected) {
            $this->markTestSkipped('No Redis server reachable at 127.0.0.1:6379.');
        }

        $this->redis->flushDB();
    }

    protected function tearDown(): void
    {
        $this->redis?->flushDB();
    }

    public function testGetReturnsNullWhenKeyMissing(): void
    {
        $storage = new RedisStorage($this->redis);

        $this->assertNull($storage->get('missing'));
    }

    public function testSetThenGetReturnsStoredState(): void
    {
        $storage = new RedisStorage($this->redis);

        $storage->set('user:1', ['tokens' => 5], ttlSeconds: 60);

        $this->assertSame(['tokens' => 5], $storage->get('user:1'));
    }

    public function testDeleteRemovesState(): void
    {
        $storage = new RedisStorage($this->redis);
        $storage->set('user:1', ['tokens' => 5], ttlSeconds: 60);

        $storage->delete('user:1');

        $this->assertNull($storage->get('user:1'));
    }

    public function testKeysAreIsolatedByPrefix(): void
    {
        $storageA = new RedisStorage($this->redis, prefix: 'app_a:');
        $storageB = new RedisStorage($this->redis, prefix: 'app_b:');

        $storageA->set('user:1', ['tokens' => 1], ttlSeconds: 60);

        $this->assertNull($storageB->get('user:1'));
    }

    public function testEntryExpiresAfterTtl(): void
    {
        $storage = new RedisStorage($this->redis);
        $storage->set('user:1', ['tokens' => 5], ttlSeconds: 1);

        sleep(2);

        $this->assertNull($storage->get('user:1'));
    }
}