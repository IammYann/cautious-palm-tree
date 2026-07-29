<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class SafeCache
{
    /**
     * Determine if the default cache store supports tagging.
     */
    public static function supportsTags(): bool
    {
        return method_exists(Cache::getStore(), 'tags');
    }

    /**
     * Cache an item using tags if supported, or direct key if not supported.
     */
    public static function remember(array $tags, string $key, int $ttl, Closure $callback): mixed
    {
        if (static::supportsTags()) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Forget a specific cached key with tags if supported, or directly if not.
     */
    public static function forget(array $tags, string $key): bool
    {
        if (static::supportsTags()) {
            return Cache::tags($tags)->forget($key);
        }

        return Cache::forget($key);
    }

    /**
     * Flush all cache items associated with specific tags,
     * or fallback to forgetting explicit keys if tags are not supported.
     */
    public static function flushTags(array $tags, array $fallbackKeys = []): bool
    {
        if (static::supportsTags()) {
            return Cache::tags($tags)->flush();
        }

        foreach ($fallbackKeys as $key) {
            Cache::forget($key);
        }

        return true;
    }
}
