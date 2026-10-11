<?php

declare(strict_types=1);

namespace RateLimiter\Tests\Middleware;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RateLimiter\Middleware\HeaderKeyResolver;

final class HeaderKeyResolverTest extends TestCase
{
    public function testResolvesConfiguredHeader(): void
    {
        $resolver = new HeaderKeyResolver('X-Client-ID', 'client:');
        $request = (new ServerRequest('GET', '/'))->withHeader('X-Client-ID', 'abc-123');

        $this->assertSame('client:abc-123', $resolver->resolve($request));
    }

    public function testUsesAnonymousKeyWhenHeaderIsAbsent(): void
    {
        $this->assertSame('header:anonymous', (new HeaderKeyResolver())->resolve(new ServerRequest('GET', '/')));
    }
}
