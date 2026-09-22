<?php

declare(strict_types=1);

namespace RateLimiter\Tests;

use PHPUnit\Framework\TestCase;
use RateLimiter\SlidingWindowLimiter;
use RateLimiter\Storage\ArrayStorage;

final class SlidingWindowLimiterTest extends TestCase
{
    public function testAllowsRequestsUpToLimit(): void
    {
        $limiter = new SlidingWindowLimiter(new ArrayStorage(), limit: 3, windowSeconds: 60);

        $r1 = $limiter->attempt('user:1');
        $r2 = $limiter->attempt('user:1');
        $r3 = $limiter->attempt('user:1');

        $this->assertTrue($r1->allowed);
        $this->assertTrue($r2->allowed);
        $this->assertTrue($r3->allowed);
        $this->assertSame(0, $r3->remaining);
    }

    public function testDeniesRequestOnceLimitReached(): void
    {
        $limiter = new SlidingWindowLimiter(new ArrayStorage(), limit: 2, windowSeconds: 60);

        $limiter->attempt('user:1');
        $limiter->attempt('user:1');
        $result = $limiter->attempt('user:1');

        $this->assertFalse($result->allowed);
        $this->assertSame(0, $result->remaining);
        $this->assertGreaterThan(0, $result->retryAfterSeconds);
    }

    public function testOldEntriesExpireOutOfWindow(): void
    {
        $limiter = new SlidingWindowLimiter(new ArrayStorage(), limit: 1, windowSeconds: 1);

        $first = $limiter->attempt('user:1');
        $this->assertTrue($first->allowed);

        $denied = $limiter->attempt('user:1');
        $this->assertFalse($denied->allowed);

        // Wait for the 1-second window to fully pass.
        usleep(1_100_000);

        $afterWindow = $limiter->attempt('user:1');
        $this->assertTrue($afterWindow->allowed);
    }

    public function testDifferentKeysHaveIndependentWindows(): void
    {
        $limiter = new SlidingWindowLimiter(new ArrayStorage(), limit: 1, windowSeconds: 60);

        $a = $limiter->attempt('user:a');
        $b = $limiter->attempt('user:b');

        $this->assertTrue($a->allowed);
        $this->assertTrue($b->allowed);
    }

    public function testCostGreaterThanOneCountsMultipleSlots(): void
    {
        $limiter = new SlidingWindowLimiter(new ArrayStorage(), limit: 5, windowSeconds: 60);

        $result = $limiter->attempt('user:1', cost: 3);

        $this->assertTrue($result->allowed);
        $this->assertSame(2, $result->remaining);
    }

    public function testCostThatWouldExceedLimitIsFullyDenied(): void
    {
        $limiter = new SlidingWindowLimiter(new ArrayStorage(), limit: 5, windowSeconds: 60);

        $limiter->attempt('user:1', cost: 3);
        $result = $limiter->attempt('user:1', cost: 3); // would be 6 > 5

        $this->assertFalse($result->allowed);
    }

    public function testRejectsInvalidLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SlidingWindowLimiter(new ArrayStorage(), limit: 0, windowSeconds: 60);
    }

    public function testRejectsInvalidWindow(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SlidingWindowLimiter(new ArrayStorage(), limit: 5, windowSeconds: 0);
    }
}