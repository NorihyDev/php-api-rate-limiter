# Examples

## Basic PSR-15 middleware demo

A runnable server showing `TokenBucketLimiter` + `RateLimitMiddleware`
enforcing 5 requests/IP, refilling at 1 token/second.

### Run it

```bash
composer install
php -S localhost:8080 -t examples/public
```

### Try it

```bash
for i in 1 2 3 4 5 6; do curl -i http://localhost:8080/; echo; done
```

The first 5 requests return `200 OK` with decreasing `X-RateLimit-Remaining`
headers. The 6th returns `429 Too Many Requests` with a `Retry-After` header.

> **Note:** this demo uses `ArrayStorage`, which only persists within a
> single PHP process. That's fine for the built-in dev server (single
> worker) but won't work across multiple workers/servers — use
> `RedisStorage` for that (see the main README).