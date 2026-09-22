<?php

declare(strict_types=1);

namespace RateLimiter\Middleware;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves the rate-limit key from the client's IP address.
 *
 * Checks X-Forwarded-For first (for requests behind a trusted proxy/load
 * balancer), falling back to the REMOTE_ADDR server param.
 */
final class IpKeyResolver implements KeyResolverInterface
{
    public function resolve(ServerRequestInterface $request): string
    {
        $forwardedFor = $request->getHeaderLine('X-Forwarded-For');

        if ($forwardedFor !== '') {
            // X-Forwarded-For can be a comma-separated list; the first is the original client.
            $ip = trim(explode(',', $forwardedFor)[0]);

            if ($ip !== '') {
                return 'ip:' . $ip;
            }
        }

        $remoteAddr = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        return 'ip:' . $remoteAddr;
    }
}