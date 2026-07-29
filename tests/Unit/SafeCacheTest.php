<?php

namespace Tests\Unit;

use App\Services\SafeCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SafeCacheTest extends TestCase
{
    public function test_safe_cache_remember_and_flush_tags(): void
    {
        $value = SafeCache::remember(['test_tag'], 'test_key', 60, function () {
            return 'cached_val';
        });

        $this->assertEquals('cached_val', $value);

        // Test flush
        SafeCache::flushTags(['test_tag'], ['test_key']);

        if (SafeCache::supportsTags()) {
            $this->assertNull(Cache::tags(['test_tag'])->get('test_key'));
        } else {
            $this->assertFalse(Cache::has('test_key'));
        }
    }
}
