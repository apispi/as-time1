<?php

namespace App\Http\Middleware;

use App\Services\UsageCounter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Counts a request toward today's total for one endpoint family: usage:api, usage:mcp. */
class CountUsage
{
    public function __construct(private readonly UsageCounter $usage) {}

    public function handle(Request $request, Closure $next, string $kind): Response
    {
        $response = $next($request);

        // Only answered requests; rate-limited (429) and malformed ones are noise.
        if ($response->getStatusCode() < 400) {
            rescue(fn () => $this->usage->record($kind), report: false);
        }

        return $response;
    }
}
