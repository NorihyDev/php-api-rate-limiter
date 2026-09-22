# Contributing

Thanks for considering a contribution! This is a small, focused library — keeping it that way is part of the goal, so please open an issue to discuss larger changes before starting work.

## Getting started

```bash
git clone https://github.com/yourusername/php-api-rate-limiter.git
cd php-api-rate-limiter
composer install
composer test
```

Requires PHP 8.1+. `ext-redis` and a local Redis instance are optional — `RedisStorageTest` auto-skips without them:

```bash
docker run -d --name rate-limiter-redis -p 6379:6379 redis:7-alpine
```

## Guidelines

- **Tests required.** Every new class or behavior change needs PHPUnit coverage. Run `composer test` before opening a PR.
- **PSR-12 style.** Keep formatting consistent with the existing code (4-space indent, `declare(strict_types=1);` at the top of every file, typed properties/params/returns).
- **No new hard dependencies in `src/`.** The core library stays dependency-free outside `psr/*` interface packages. `RedisStorage` uses `ext-redis` as an optional extension, not a Composer package, to keep it lightweight. If you need something like `nyholm/psr7`, it belongs in `require-dev` only.
- **One concern per PR.** Small, focused PRs are easier to review and merge quickly.
- **Update the README** if you're adding a new public class or changing usage.

## Adding a new rate limiting algorithm

1. Implement `RateLimiterInterface` in `src/`.
2. Constructor should accept a `StorageInterface` plus your algorithm's parameters (mirror the style of `TokenBucketLimiter`/`SlidingWindowLimiter`).
3. Validate constructor arguments and throw `InvalidArgumentException` for invalid ones.
4. Add a full PHPUnit test class covering: normal operation, limit/denial behavior, independent keys, edge cases (zero/negative cost, etc.).
5. Add a short usage example to the README's Quick Start section.

## Adding a new storage backend

1. Implement `StorageInterface` in `src/Storage/`.
2. If it needs an external service (like Redis does), make the test auto-skip when that service isn't available — see `RedisStorageTest::setUp()` for the pattern.
3. Document any required PHP extension or package in `composer.json`'s `suggest` block, not `require`.

## Reporting bugs

Open an issue with:
- PHP version and OS
- Minimal code to reproduce
- Expected vs. actual behavior

## Questions

Open a [Discussion](../../discussions) or an issue — happy to help.