<?php

declare(strict_types=1);

namespace RateLimiter\Tests\Storage;

use PHPUnit\Framework\TestCase;
use RateLimiter\Storage\ArrayStorage;

final class ArrayStorageTest extends TestCase
{
    public function testGetReturnsNullWhenKeyMissing(): void
    {
        $storage = new ArrayStorage();

        $this->assertNull($storage->get('missing'));
    }

    public function testSetThenGetReturnsStoredState(): void
    {
        $storage = new ArrayStorage();

        $storage->set('user:1', ['tokens' => 5], ttlSeconds: 60);

        $this->assertSame(['tokens' => 5], $storage->get('user:1'));
    }

    public function testDeleteRemovesState(): void
    {
        $storage = new ArrayStorage();
        $storage->set('user:1', ['tokens' => 5], ttlSeconds: 60);

        $storage->delete('user:1');

        $this->assertNull($storage->get('user:1'));
    }

    public function testEntryExpiresAfterTtl(): void
    {
        $storage = new ArrayStorage();

        // ttlSeconds = 0 means it should already be expired "now"
        $storage->set('user:1', ['tokens' => 5], ttlSeconds: -1);

        $this->assertNull($storage->get('user:1'));
    }

    public function testOverwritingKeyReplacesState(): void
    {
        $storage = new ArrayStorage();
        $storage->set('user:1', ['tokens' => 5], ttlSeconds: 60);
        $storage->set('user:1', ['tokens' => 9], ttlSeconds: 60);

        $this->assertSame(['tokens' => 9], $storage->get('user:1'));
    }
}