# PHP API Rate Limiter

[![CI](https://github.com/yourusername/php-api-rate-limiter/actions/workflows/ci.yml/badge.svg)](https://github.com/yourusername/php-api-rate-limiter/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-777bb4)](composer.json)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A lightweight, framework-agnostic PHP rate limiter for APIs. No dependencies required for the core algorithms — bring your own storage, bring your own framework, or use the included PSR-15 middleware.

- 🪣 **Token Bucket** — smooth limiting with burst tolerance
- 🪟 **Sliding Window** — strict "N requests per window" guarantee
- 🔌 **Pluggable storage** — in-memory (`ArrayStorage`) or shared (`RedisStorage`)
- 🧩 **PSR-15 middleware** included, works with any PSR-7 framework (Slim, Laminas, Mezzio, etc.)
- ✅ Fully tested, CI-checked on PHP 8.1 – 8.3

## Installation

```bash
composer require yourusername/php-api-rate-limiter
```

For `RedisStorage`, you'll also need the `ext-redis` PHP extension installed.

## Quick start

### Plain usage (no HTTP framework)

```php
use RateLimiter\Storage\ArrayStorage;
use RateLimiter\TokenBucketLimiter;

$limiter = new TokenBucketLimiter(
    storage: new ArrayStorage(),
    capacity: 10,     // allow bursts up to 10 requests
    refillRate: 2.0,  // then refill at 2 requests/second
);

$result = $limiter->attempt('user:42');

if (!$result->allowed) {
    http_response_code(429);
    header('Retry-After: ' . $result->retryAfterSeconds);
    exit('Too many requests');
}

// proceed with the request...
```

### As PSR-15 middleware

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use RateLimiter\Middleware\RateLimitMiddleware;
use RateLimiter\Storage\ArrayStorage;
use RateLimiter\TokenBucketLimiter;

$limiter = new TokenBucketLimiter(new ArrayStorage(), capacity: 100, refillRate: 10.0);
$middleware = new RateLimitMiddleware($limiter, new Psr17Factory());

// Add $middleware to your framework's middleware stack (Slim, Mezzio, etc.)
```

Denied requests automatically get a `429` JSON response with `Retry-After`, `X-RateLimit-Limit`, and `X-RateLimit-Remaining` headers. Allowed requests get `X-RateLimit-*` headers stamped on the way out.

### Sliding Window (strict limiting)

```php
use RateLimiter\SlidingWindowLimiter;
use RateLimiter\Storage\ArrayStorage;

$limiter = new SlidingWindowLimiter(
    storage: new ArrayStorage(),
    limit: 100,        // exactly 100 requests
    windowSeconds: 60, // per rolling 60-second window
);
```

### Production: shared state with Redis

`ArrayStorage` only lives inside one PHP process. For anything with multiple workers or servers, swap in `RedisStorage`:

```php
use RateLimiter\Storage\RedisStorage;

$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);

$storage = new RedisStorage($redis, prefix: 'my_app:');
$limiter = new TokenBucketLimiter($storage, capacity: 100, refillRate: 10.0);
```

### Custom key resolution

By default, the middleware keys by client IP (honoring `X-Forwarded-For`). To key by API key,