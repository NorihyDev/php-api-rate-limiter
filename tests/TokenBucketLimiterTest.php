<?php

declare(strict_types=1);

namespace RateLimiter\Tests;

use PHPUnit\Framework\TestCase;
use RateLimiter\Storage\ArrayStorage;
use RateLimiter\TokenBucketLimiter;

final class TokenBucketLimiterTest extends TestCase
{
    public function testAllowsRequestsUpToCapacity(): void
    {
        $limiter = new TokenBucketLimiter(new ArrayStorage(), capacity: 3, refillRate: 1.0);

        $r1 = $limiter->attempt('user:1');
        $r2 = $limiter->attempt('user:1');
        $r3 = $limiter->attempt('user:1');

        $this->assertTrue($r1->allowed);
        $this->assertTrue($r2->allowed);
        $this->assertTrue($r3->allowed);
        $this->assertSame(0, $r3->remaining);
    }

    public function testDeniesRequestOnceBucketIsEmpty(): void
    {
        $limiter = new TokenBucketLimiter(new ArrayStorage(), capacity: 1, refillRate: 1.0);

        $limiter->attempt('user:1');
        $result = $limiter->attempt('user:1');

        $this->assertFalse($result->allowed);
        $this->assertSame(0, $result->remaining);
        $this->assertGreaterThan(0, $result->retryAfterSeconds);
    }

    public function testDifferentKeysHaveIndependentBuckets(): void
    {
        $limiter = new TokenBucketLimiter(new ArrayStorage(), capacity: 1, refillRate: 1.0);

        $a = $limiter->attempt('user:a');
        $b = $limiter->attempt('user:b');

        $this->assertTrue($a->allowed);
        $this->assertTrue($b->allowed);
    }

    public function testRefillsOverTime(): void
    {
        $limiter = new TokenBucketLimiter(new ArrayStorage(), capacity: 1, refillRate: 10.0);

        $first = $limiter->attempt('user:1');
        $this->assertTrue($first->allowed);

        // Bucket is now empty; wait long enough for >=1 token to refill.
        usleep(150_000); // 0.15s * 10 tokens/s = 1.5 tokens refilled

        $second = $limiter->attempt('user:1');
        $this->assertTrue($second->allowed);
    }

    public function testCostGreaterThanOneConsumesMultipleTokens(): void
    {
        $limiter = new TokenBucketLimiter(new ArrayStorage(), capacity: 5, refillRate: 1.0);

        $result = $limiter->attempt('user:1', cost: 3);

        $this->assertTrue($result->allowed);
        $this->assertSame(2, $result->remaining);
    }

    public function testRejectsInvalidCapacity(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TokenBucketLimiter(new ArrayStorage(), capacity: 0, refillRate: 1.0);
    }

    public function testRejectsInvalidCost(): void
    {
        $limiter = new TokenBucketLimiter(new ArrayStorage(), capacity: 1, refillRate: 1.0);

        $this->expectException(\InvalidArgumentException::class);

        $limiter->attempt('user:1', cost: 0);
    }
}