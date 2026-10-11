<?php

declare(strict_types=1);

namespace RateLimiter\Middleware;

use Psr\Http\Message\ServerRequestInterface;

/** Resolves a client key from a request header, such as X-API-Key. */
final class HeaderKeyResolver implements KeyResolverInterface
{
    public function __construct(
        private readonly string $headerName = 'X-API-Key',
        private readonly string $prefix = 'header:',
    ) {
    }

    public function resolve(ServerRequestInterface $request): string
    {
        $value = trim($request->getHeaderLine($this->headerName));

        return $value === '' ? $this->prefix . 'anonymous' : $this->prefix . $value;
    }
}
