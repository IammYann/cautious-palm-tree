<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequestsRedis
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 5, int $decayMinutes = 1): Response
    {
        $key = $this->resolveKey($request);
        $attempts = (int) Redis::connection('default')->get($key);

        if ($attempts >= $maxAttempts) {
            return response('Too many requests. Please try again later.', 429)
                ->withHeaders([
                    'Retry-After' => $decayMinutes * 60,
                ]);
        }

        Redis::connection('default')->incr($key);
        Redis::connection('default')->expire($key, $decayMinutes * 60);

        return $next($request);
    }

    protected function resolveKey(Request $request): string
    {
        $routeName = $request->route()?->getName() ?? 'generic';
        $identifier = $request->input('email') ?? $request->user()?->email ?? $request->ip();

        return sprintf('throttle:%s:%s', $routeName, sha1($request->ip() . ':' . $identifier));
    }
}
