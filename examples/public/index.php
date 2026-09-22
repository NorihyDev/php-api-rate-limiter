<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RateLimiter\Middleware\RateLimitMiddleware;
use RateLimiter\Storage\ArrayStorage;
use RateLimiter\TokenBucketLimiter;

/*
 * Demo server: 5 requests per IP, refilling at 1 token/second (capacity 5).
 *
 * Run it with:
 *   php -S localhost:8080 -t examples/public
 *
 * Then hit it repeatedly:
 *   for i in 1 2 3 4 5 6; do curl -i http://localhost:8080/; echo; done
 *
 * Note: ArrayStorage is per-process, so state only persists across
 * requests when using the built-in server's single worker. In real
 * multi-worker deployments, swap in RedisStorage (see Step 7 / README).
 */

$factory = new Psr17Factory();
$storage = new ArrayStorage();
$limiter = new TokenBucketLimiter($storage, capacity: 5, refillRate: 1.0);
$middleware = new RateLimitMiddleware($limiter, $factory);

$creator = new ServerRequestCreator($factory, $factory, $factory, $factory);
$request = $creator->fromGlobals();

$finalHandler = new class implements RequestHandlerInterface {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $factory = new Psr17Factory();
        $response = $factory->createResponse(200);
        $response->getBody()->write(json_encode([
            'message' => 'Request succeeded!',
            'path' => $request->getUri()->getPath(),
        ], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
};

$response = $middleware->process($request, $finalHandler);

// Emit the PSR-7 response since the built-in server needs raw output.
http_response_code($response->getStatusCode());

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

echo $response->getBody();