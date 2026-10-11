<?php

declare(strict_types=1);

namespace RateLimiter\Tests;

use PHPUnit\Framework\TestCase;
use RateLimiter\FixedWindowLimiter;
use RateLimiter\Storage\ArrayStorage;

final class FixedWindowLimiterTest extends TestCase
{
    public function testAllowsUpToTheConfiguredLimit(): void
    {
        $limiter = new FixedWindowLimiter(new ArrayStorage(), 2, 60);

        $this->assertTrue($limiter->attempt('client')->allowed);
        $this->assertTrue($limiter->attempt('client')->allowed);
        $denied = $limiter->attempt('client');

        $this->assertFalse($denied->allowed);
        $this->assertGreaterThan(0, $denied->retryAfterSeconds);
    }

    public function testKeepsKeysIndependent(): void
    {
        $limiter = new FixedWindowLimiter(new ArrayStorage(), 1, 60);

        $this->assertTrue($limiter->attempt('a')->allowed);
        $this->assertTrue($limiter->attempt('b')->allowed);
    }
}
